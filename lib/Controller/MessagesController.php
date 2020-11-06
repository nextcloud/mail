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
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
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
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use function array_map;

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

	/** @var LoggerInterface */
	private $logger;

	/** @var Folder */
	private $userFolder;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ContentSecurityPolicyNonceManager */
	private $nonceManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param IMailManager $mailManager
	 * @param IMailSearch $mailSearch
	 * @param ItineraryService $itineraryService
	 * @param string $UserId
	 * @param $userFolder
	 * @param LoggerInterface $logger
	 * @param IL10N $l10n
	 * @param IMimeTypeDetector $mimeTypeDetector
	 * @param IURLGenerator $urlGenerator
	 * @param ContentSecurityPolicyNonceManager $nonceManager
	 */
	public function __construct(string $appName,
								IRequest $request,
								AccountService $accountService,
								IMailManager $mailManager,
								IMailSearch $mailSearch,
								ItineraryService $itineraryService,
								string $UserId,
								$userFolder,
								LoggerInterface $logger,
								IL10N $l10n,
								IMimeTypeDetector $mimeTypeDetector,
								IURLGenerator $urlGenerator,
								ContentSecurityPolicyNonceManager $nonceManager) {
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
		$this->nonceManager = $nonceManager;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $mailboxId
	 * @param int $cursor
	 * @param string $filter
	 * @param int|null $limit
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function index(int $mailboxId,
						  int $cursor = null,
						  string $filter = null,
						  int $limit = null): JSONResponse {
		try {
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $mailboxId);
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("loading messages of folder <$mailboxId>");

		return new JSONResponse(
			$this->mailSearch->findMessages(
				$account,
				$mailbox,
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
	public function show(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("loading message <$id>");

		return new JSONResponse(
			$this->mailSearch->findMessage(
				$account,
				$mailbox,
				$message
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getBody(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$json = $this->mailManager->getImapMessage(
			$account,
			$mailbox,
			$message->getUid(),
			true
		)->getFullMessage($id);
		$json['itineraries'] = $this->itineraryService->extract(
			$account,
			$mailbox->getName(),
			$message->getUid()
		);
		$json['attachments'] = array_map(function ($a) use ($id) {
			return $this->enrichDownloadUrl(
				$id,
				$a
			);
		}, $json['attachments']);
		$json['accountId'] = $account->getId();
		$json['mailboxId'] = $mailbox->getId();
		$json['databaseId'] = $message->getId();

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function getThread(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		return new JSONResponse($this->mailManager->getThread($account, $id));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param int $destFolderId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function move(int $id, int $destFolderId): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$dstMailbox = $this->mailManager->getMailbox($this->currentUserId, $destFolderId);
			$srcAccount = $this->accountService->find($this->currentUserId, $srcMailbox->getAccountId());
			$dstAccount = $this->accountService->find($this->currentUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->moveMessage(
			$srcAccount,
			$srcMailbox->getName(),
			$message->getUid(),
			$dstAccount,
			$dstMailbox->getName()
		);
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
	 * @return JSONResponse
	 * @throws ServiceException
	 */
	public function getSource(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$response = new JSONResponse([
			'source' => $this->mailManager->getSource(
				$account,
				$mailbox->getName(),
				$message->getUid()
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
	 * @param int $id
	 * @param bool $plain do not inject scripts if true (default=false)
	 *
	 * @return HtmlResponse|TemplateResponse
	 *
	 * @throws ClientException
	 */
	public function getHtmlBody(int $id, bool $plain=false): Response {
		try {
			try {
				$message = $this->mailManager->getMessage($this->currentUserId, $id);
				$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
				$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
			} catch (DoesNotExistException $e) {
				return new TemplateResponse(
					$this->appName,
					'error',
					['message' => 'Not allowed'],
					'none'
				);
			}

			$html = $this->mailManager->getImapMessage(
				$account,
				$mailbox,
				$message->getUid(),
				true
			)->getHtmlBody(
				$id
			);
			$htmlResponse = $plain ?
				HtmlResponse::plain($html) :
				HtmlResponse::withResizer(
					$html,
					$this->nonceManager->getNonce(),
					$this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->linkTo('mail', 'js/htmlresponse.js')
					)
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
	 * @param int $id
	 * @param string $attachmentId
	 *
	 * @return Response
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function downloadAttachment(int $id,
									   string $attachmentId): Response {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$folder = $account->getMailbox($mailbox->getName());
		$attachment = $folder->getAttachment($message->getUid(), $attachmentId);

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
	 * @param int $id
	 * @param string $attachmentId
	 * @param string $targetPath
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function saveAttachment(int $id,
								   string $attachmentId,
								   string $targetPath) {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$folder = $account->getMailbox($mailbox->getName());

		if ($attachmentId === '0') {
			// Save all attachments
			/* @var $m IMAPMessage */
			$m = $folder->getMessage($message->getUid());
			$attachmentIds = array_map(function ($a) {
				return $a['id'];
			}, $m->attachments);
		} else {
			$attachmentIds = [$attachmentId];
		}

		foreach ($attachmentIds as $aid) {
			$attachment = $folder->getAttachment($message->getUid(), $aid);

			$fileName = $attachment->getName() ?? $this->l10n->t('Embedded message %s', [
				$aid,
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
	 * @param int $id
	 * @param array $flags
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function setFlags(int $id, array $flags): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		foreach ($flags as $flag => $value) {
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
			$this->mailManager->flagMessage($account, $mailbox->getName(), $message->getUid(), $flag, $value);
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
	public function destroy(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("deleting message <$id>");

		$this->mailManager->deleteMessage(
			$account,
			$mailbox->getName(),
			$message->getUid()
		);
		return new JSONResponse();
	}

	/**
	 * @param int $id
	 * @param array $attachment
	 *
	 * @return array
	 */
	private function enrichDownloadUrl(int $id,
									   array $attachment) {
		$downloadUrl = $this->urlGenerator->linkToRoute('mail.messages.downloadAttachment',
			[
				'id' => $id,
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
