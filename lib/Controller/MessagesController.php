<?php

/**
 * @author Alexander Weidinger <alexwegoo@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Jakob Sack <jakob@owncloud.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\IMailBox;
use OCA\Mail\Service\Logger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class MessagesController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var IMailManager */
	private $mailManager;

	/** @var string */
	private $currentUserId;

	/** @var Logger */
	private $logger;

	/** @var Folder */
	private $userFolder;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Account[] */
	private $accounts = [];

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string $UserId
	 * @param $userFolder
	 * @param Logger $logger
	 * @param IL10N $l10n
	 * @param IMimeTypeDetector $mimeTypeDetector
	 * @param IURLGenerator $urlGenerator
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct($appName, IRequest $request,
		AccountService $accountService, IMailManager $mailManager, $UserId,
		$userFolder, Logger $logger, IL10N $l10n, IMimeTypeDetector $mimeTypeDetector,
		IURLGenerator $urlGenerator, ITimeFactory $timeFactory) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->userFolder = $userFolder;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->urlGenerator = $urlGenerator;
		$this->timeFactory = $timeFactory;
		$this->mailManager = $mailManager;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $cursor
	 * @param string $filter
	 * @return JSONResponse
	 */
	public function index($accountId, $folderId, $cursor = null, $filter = null) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$this->logger->debug("loading messages of folder <$folderId>");

		if ($cursor === '') {
			$cursor = null;
		}
		$messages = $mailBox->getMessages($filter, $cursor);

		$json = array_map(function($j) use ($mailBox) {
			if ($mailBox->getSpecialRole() === 'trash') {
				$j['delete'] = (string) $this->l10n->t('Delete permanently');
			}
			return $j;
		}, $messages);

		return new JSONResponse($json);
	}

	/**
	 * @param integer $accountId
	 * @param string $folderId
	 */
	private function loadMessage($accountId, $folderId, $id) {
		$account = $this->getAccount($accountId);
		$mailBox = $account->getMailbox(base64_decode($folderId));
		/* @var $message IMAPMessage */
		$message = $mailBox->getMessage($id);

		$json = $this->enhanceMessage($accountId, $folderId, $id, $message, $mailBox);

		// Unified inbox hack
		// TODO: evalue whether this is still in use on the client side
		$messageId = $id;
		$json['messageId'] = $messageId;
		$json['accountId'] = $accountId;
		$json['folderId'] = $folderId;
		// End unified inbox hack

		return $json;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param mixed $id
	 * @return JSONResponse
	 */
	public function show($accountId, $folderId, $id) {
		return new JSONResponse($this->loadMessage($accountId, $folderId, $id));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $id
	 * @param int $destAccountId
	 * @param string $destFolderId
	 * @return JSONResponse
	 */
	public function move($accountId, $folderId, $id, $destAccountId, $destFolderId) {
		$srcAccount = $this->accountService->find($this->currentUserId, $accountId);
		$dstAccount = $this->accountService->find($this->currentUserId,
			$destAccountId);
		$this->mailManager->moveMessage($srcAccount, base64_decode($folderId), $id,
			$dstAccount, base64_decode($destFolderId));
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @return HtmlResponse|TemplateResponse
	 */
	public function getHtmlBody($accountId, $folderId, $messageId) {
		try {
			$mailBox = $this->getFolder($accountId, $folderId);

			$m = $mailBox->getMessage($messageId, true);
			$html = $m->getHtmlBody($accountId, $folderId, $messageId,
				function($cid) use ($m) {
				$match = array_filter($m->attachments,
					function($a) use($cid) {
					return $a['cid'] === $cid;
				});
				$match = array_shift($match);
				if (is_null($match)) {
					return null;
				}
				return $match['id'];
			});

			$htmlResponse = new HtmlResponse($html);

			// Harden the default security policy
			$policy = new ContentSecurityPolicy();
			$policy->allowEvalScript(false);
			$policy->disallowScriptDomain('\'self\'');
			$policy->disallowConnectDomain('\'self\'');
			$policy->disallowFontDomain('\'self\'');
			$policy->disallowMediaDomain('\'self\'');
			$htmlResponse->setContentSecurityPolicy($policy);

			// Enable caching
			$htmlResponse->setCacheHeaders(60 * 60, $this->timeFactory);
			$htmlResponse->addHeader('Pragma', 'cache');

			return $htmlResponse;
		} catch (\Exception $ex) {
			return new TemplateResponse($this->appName, 'error',
				['message' => $ex->getMessage()], 'none');
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @param string $attachmentId
	 * @return AttachmentDownloadResponse
	 */
	public function downloadAttachment($accountId, $folderId, $messageId,
		$attachmentId) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		return new AttachmentDownloadResponse(
			$attachment->getContents(), $attachment->getName(), $attachment->getType());
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @param int $attachmentId
	 * @param string $targetPath
	 * @return JSONResponse
	 */
	public function saveAttachment($accountId, $folderId, $messageId,
		$attachmentId, $targetPath) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$attachmentIds = [$attachmentId];
		if ($attachmentId === 0) {
			// Save all attachments
			/* @var $m IMAPMessage */
			$m = $mailBox->getMessage($messageId);
			$attachmentIds = array_map(function($a) {
				return $a['id'];
			}, $m->attachments);
		}

		foreach ($attachmentIds as $attachmentId) {
			$attachment = $mailBox->getAttachment($messageId, $attachmentId);

			$fileName = $attachment->getName();
			$fileParts = pathinfo($fileName);
			$fileName = $fileParts['filename'];
			$fileExtension = $fileParts['extension'];
			$fullPath = "$targetPath/$fileName.$fileExtension";
			$counter = 2;
			while ($this->userFolder->nodeExists($fullPath)) {
				$fullPath = "$targetPath/$fileName ($counter).$fileExtension";
				$counter++;
			}

			$newFile = $this->userFolder->newFile($fullPath);
			$newFile->putContent($attachment->getContents());
		}
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @param array $flags
	 * @return JSONResponse
	 */
	public function setFlags($accountId, $folderId, $messageId, $flags) {
		$mailBox = $this->getFolder($accountId, $folderId);

		foreach ($flags as $flag => $value) {
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
			if ($flag === 'unseen') {
				$flag = 'seen';
				$value = !$value;
			}
			$mailBox->setMessageFlag($messageId, '\\' . $flag, $value);
		}
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $id
	 * @return JSONResponse
	 */
	public function destroy($accountId, $folderId, $id) {
		$this->logger->debug("deleting message <$id> of folder <$folderId>, account <$accountId>");
		try {
			$account = $this->getAccount($accountId);
			$account->deleteMessage(base64_decode($folderId), $id);
			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			$this->logger->error("could not delete message <$id> of folder <$folderId>, "
				. "account <$accountId> because it does not exist");
			throw $e;
		}
	}

	/**
	 * @param int $accountId
	 * @return Account
	 */
	private function getAccount($accountId) {
		if (!array_key_exists($accountId, $this->accounts)) {
			$this->accounts[$accountId] = $this->accountService->find($this->currentUserId,
				$accountId);
		}
		return $this->accounts[$accountId];
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 * @return IMailBox
	 */
	private function getFolder($accountId, $folderId) {
		$account = $this->getAccount($accountId);
		return $account->getMailbox(base64_decode($folderId));
	}

	/**
	 * @param string $messageId
	 * @param int $accountId
	 * @param string $folderId
	 * @return callable
	 */
	private function enrichDownloadUrl($accountId, $folderId, $messageId,
		$attachment) {
		$downloadUrl = $this->urlGenerator->linkToRoute('mail.messages.downloadAttachment',
			[
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $messageId,
			'attachmentId' => $attachment['id'],
		]);
		$downloadUrl = $this->urlGenerator->getAbsoluteURL($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		$attachment['mimeUrl'] = $this->mimeTypeDetector->mimeTypeIcon($attachment['mime']);

		$attachment['isImage'] = $this->attachmentIsImage($attachment);
		$attachment['isCalendarEvent'] = $this->attachmentIsCalendarEvent($attachment);

		return $attachment;
	}

	/**
	 * @param array $attachment
	 *
	 * Determines if the content of this attachment is an image
	 *
	 * @return boolean
	 */
	private function attachmentIsImage($attachment) {
		return in_array(
			$attachment['mime'], [
			'image/jpeg',
			'image/png',
			'image/gif'
		]);
	}

	/**
	 * @param array $attachment
	 * @return boolean
	 */
	private function attachmentIsCalendarEvent($attachment) {
		return $attachment['mime'] === 'text/calendar';
	}

	/**
	 * @param string $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @return string
	 */
	private function buildHtmlBodyUrl($accountId, $folderId, $messageId) {
		$htmlBodyUrl = $this->urlGenerator->linkToRoute('mail.messages.getHtmlBody',
			[
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $messageId,
		]);
		return $this->urlGenerator->getAbsoluteURL($htmlBodyUrl);
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $id
	 * @param IMAPMessage $m
	 * @param IMailBox $mailBox
	 * @return array
	 */
	private function enhanceMessage($accountId, $folderId, $id, IMAPMessage $m,
		$mailBox) {
		$json = $m->getFullMessage($mailBox->getSpecialRole());

		if (isset($json['hasHtmlBody'])) {
			$json['htmlBodyUrl'] = $this->buildHtmlBodyUrl($accountId, $folderId, $id);
		}

		if (isset($json['attachments'])) {
			$json['attachments'] = array_map(function ($a) use ($accountId, $folderId, $id) {
				return $this->enrichDownloadUrl($accountId, $folderId, $id, $a);
			}, $json['attachments']);

			return $json;
		}
		return $json;
	}

}
