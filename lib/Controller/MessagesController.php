<?php

declare(strict_types=1);

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

use Exception;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\IMailBox;
use OCA\Mail\Service\ItineraryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use function array_map;
use function base64_decode;

class MessagesController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var IMailManager */
	private $mailManager;

	/** @var IMailSearch */
	private $mailSearch;

	/** @var ItineraryService */
	private $itineraryService;

	/** @var string */
	private $currentUserId;

	/** @var ILogger */
	private $logger;

	/** @var Folder */
	private $userFolder;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string $UserId
	 * @param $userFolder
	 * @param ILogger $logger
	 * @param IL10N $l10n
	 * @param IMimeTypeDetector $mimeTypeDetector
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(string $appName,
								IRequest $request,
								AccountService $accountService,
								IMailManager $mailManager,
								IMailSearch $mailSearch,
								ItineraryService $itineraryService,
								string $UserId,
								$userFolder,
								ILogger $logger,
								IL10N $l10n,
								IMimeTypeDetector $mimeTypeDetector,
								IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);

		$this->accountService = $accountService;
		$this->mailManager = $mailManager;
		$this->mailSearch = $mailSearch;
		$this->itineraryService = $itineraryService;
		$this->currentUserId = $UserId;
		$this->userFolder = $userFolder;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->urlGenerator = $urlGenerator;
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
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function index(int $accountId, string $folderId, int $cursor = null, string $filter = null, int $limit = null): JSONResponse {
		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(null, Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("loading messages of folder <$folderId>");

		return new JSONResponse(
			$this->mailSearch->findMessages(
				$account,
				base64_decode($folderId),
				$filter === '' ? null : $filter,
				$cursor,
				$limit
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function show(int $accountId, string $folderId, int $id): JSONResponse {
		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(null, Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("loading message of folder <$folderId>");

		return new JSONResponse(
			$this->mailSearch->findMessage(
				$account,
				base64_decode($folderId),
				$id
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getBody(int $accountId, string $folderId, int $messageId): JSONResponse {
		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(null, Http::STATUS_FORBIDDEN);
		}

		$json = $this->mailManager->getMessage(
			$account,
			base64_decode($folderId),
			$messageId,
			true
		)->getFullMessage(
			$accountId,
			base64_decode($folderId),
			$messageId
		);
		$json['itineraries'] = $this->itineraryService->extract(
			$account,
			base64_decode($folderId),
			$messageId
		);
		$json['attachments'] = array_map(function ($a) use ($accountId, $folderId, $messageId) {
			return $this->enrichDownloadUrl($accountId, $folderId, $messageId, $a);
		}, $json['attachments']);

		return new JSONResponse($json);
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
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function move($accountId, $folderId, $id, $destAccountId, $destFolderId): JSONResponse {
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
	 * @param int $messageId
	 *
	 * @return HtmlResponse|TemplateResponse
	 * @throws ServiceException
	 */
	public function getSource(int $accountId, string $folderId, int $messageId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		$response = new JSONResponse([
			'source' => $this->mailManager->getSource(
				$account,
				base64_decode($folderId),
				$messageId
			)
		]);

		// Enable caching
		$response->cacheFor(60 * 60);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 *
	 * @return HtmlResponse|TemplateResponse
	 *
	 * @throws ClientException
	 */
	public function getHtmlBody(int $accountId, string $folderId, int $messageId): Response {
		try {
			try {
				$account = $this->accountService->find($this->currentUserId, $accountId);
			} catch (DoesNotExistException $e) {
				return new TemplateResponse(
					$this->appName,
					'error',
					['message' => 'Not allowed'],
					'none'
				);
			}

			$htmlResponse = new HtmlResponse(
				$this->mailManager->getMessage(
					$account,
					base64_decode($folderId),
					$messageId,
					true
				)->getHtmlBody($accountId, base64_decode($folderId), $messageId)
			);

			// Harden the default security policy
			$policy = new ContentSecurityPolicy();
			$policy->allowEvalScript(false);
			$policy->disallowScriptDomain('\'self\'');
			$policy->disallowConnectDomain('\'self\'');
			$policy->disallowFontDomain('\'self\'');
			$policy->disallowMediaDomain('\'self\'');
			$htmlResponse->setContentSecurityPolicy($policy);

			// Enable caching
			$htmlResponse->cacheFor(60 * 60);

			return $htmlResponse;
		} catch (Exception $ex) {
			return new TemplateResponse(
				$this->appName,
				'error',
				['message' => $ex->getMessage()],
				'none'
			);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 * @param int $attachmentId
	 *
	 * @return AttachmentDownloadResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function downloadAttachment(int $accountId, string $folderId, int $messageId,
									   string $attachmentId) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		// Body party and embedded messages do not have a name
		if ($attachment->getName() === null) {
			return new AttachmentDownloadResponse(
				$attachment->getContents(),
				$this->l10n->t('Embedded message %s', [
					$attachmentId,
				]) . '.eml',
				$attachment->getType()
			);
		}
		return new AttachmentDownloadResponse(
			$attachment->getContents(),
			$attachment->getName(),
			$attachment->getType()
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 * @param int $attachmentId
	 * @param string $targetPath
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function saveAttachment(int $accountId, string $folderId, int $messageId,
								   string $attachmentId, string $targetPath) {
		$mailBox = $this->getFolder($accountId, $folderId);

		if ($attachmentId === '0') {
			// Save all attachments
			/* @var $m IMAPMessage */
			$m = $mailBox->getMessage($messageId);
			$attachmentIds = array_map(function ($a) {
				return $a['id'];
			}, $m->attachments);
		} else {
			$attachmentIds = [$attachmentId];
		}

		foreach ($attachmentIds as $attachmentId) {
			$attachment = $mailBox->getAttachment($messageId, $attachmentId);

			$fileName = $attachment->getName() ?? $this->l10n->t('Embedded message %s', [
				$attachmentId,
			]) . '.eml';
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
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function setFlags(int $accountId, string $folderId, int $messageId, array $flags): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		foreach ($flags as $flag => $value) {
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
			$this->mailManager->flagMessage($account, base64_decode($folderId), $messageId, $flag, $value);
		}
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function destroy(int $accountId, string $folderId, int $id): JSONResponse {
		$this->logger->debug("deleting message <$id> of folder <$folderId>, account <$accountId>");

		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(null, Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->deleteMessage($account, base64_decode($folderId), $id);
		return new JSONResponse();
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 *
	 * @return IMailBox
	 * @deprecated
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	private function getFolder(int $accountId, string $folderId): IMailBox {
		$account = $this->accountService->find($this->currentUserId, $accountId);
		return $account->getMailbox(base64_decode($folderId));
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 * @param array $attachment
	 *
	 * @return array
	 */
	private function enrichDownloadUrl(int $accountId, string $folderId, int $messageId,
									   array $attachment) {
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
	private function attachmentIsImage(array $attachment): bool {
		return in_array(
			$attachment['mime'], [
				'image/jpeg',
				'image/png',
				'image/gif'
			]);
	}

	/**
	 * @param array $attachment
	 *
	 * @return boolean
	 */
	private function attachmentIsCalendarEvent(array $attachment): bool {
		return in_array($attachment['mime'], ['text/calendar', 'application/ics'], true);
	}
}
