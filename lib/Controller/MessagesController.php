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
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
use Horde_Mime_Exception;
use Horde_Mime_Part;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\SMimeData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\SMimeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ZipResponse;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use function array_map;

class MessagesController extends Controller {
	private AccountService $accountService;
	private IMailManager $mailManager;
	private IMailSearch $mailSearch;
	private ItineraryService $itineraryService;
	private ?string $currentUserId;
	private LoggerInterface $logger;
	private ?Folder $userFolder;
	private IMimeTypeDetector $mimeTypeDetector;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
	private ContentSecurityPolicyNonceManager $nonceManager;
	private ITrustedSenderService $trustedSenderService;
	private IMailTransmission $mailTransmission;
	private SMimeService $sMimeService;
	private IMAPClientFactory $clientFactory;

	public function __construct(string $appName,
								IRequest $request,
								AccountService $accountService,
								IMailManager $mailManager,
								IMailSearch $mailSearch,
								ItineraryService $itineraryService,
								?string $UserId,
								$userFolder,
								LoggerInterface $logger,
								IL10N $l10n,
								IMimeTypeDetector $mimeTypeDetector,
								IURLGenerator $urlGenerator,
								ContentSecurityPolicyNonceManager $nonceManager,
								ITrustedSenderService $trustedSenderService,
								IMailTransmission $mailTransmission,
								SMimeService $sMimeService,
								IMAPClientFactory $clientFactory) {
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
		$this->nonceManager = $nonceManager;
		$this->trustedSenderService = $trustedSenderService;
		$this->mailTransmission = $mailTransmission;
		$this->sMimeService = $sMimeService;
		$this->clientFactory = $clientFactory;
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

		$client = $this->clientFactory->getClient($account);
		try {
			$json = $this->mailManager->getImapMessage(
				$client,
				$account,
				$mailbox,
				$message->getUid(), true
			)->getFullMessage($id);
			$rawMessage = $this->mailManager->getSource(
				$client,
				$account,
				$mailbox->getName(),
				$message->getUid(),
			);
		} finally {
			$client->logout();
		}

		$itineraries = $this->itineraryService->getCached($account, $mailbox, $message->getUid());
		if ($itineraries) {
			$json['itineraries'] = $itineraries;
		}
		$json['attachments'] = array_map(function ($a) use ($id) {
			return $this->enrichDownloadUrl(
				$id,
				$a
			);
		}, $json['attachments']);
		$json['accountId'] = $account->getId();
		$json['mailboxId'] = $mailbox->getId();
		$json['databaseId'] = $message->getId();
		$json['isSenderTrusted'] = $this->isSenderTrusted($message);

		$sMimeData = new SMimeData();
		try {
			$parsedMessage = Horde_Mime_Part::parseMessage($rawMessage, ['no_body' => true]);
			if ($parsedMessage->getType() === 'multipart/signed') {
				$sMimeData->setIsSigned(true);
				$sMimeData->setSignatureIsValid($this->sMimeService->verifyMessage($rawMessage));
			}
		} catch (Horde_Mime_Exception $e) {
			$this->logger->warning('Failed to parse MIME message', ['error' => $e]);
		}
		$json['sMime'] = $sMimeData;

		$response = new JSONResponse($json);

		// Enable caching
		$response->cacheFor(60 * 60, false, true);

		return $response;
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
	 */
	public function getItineraries(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$response = new JsonResponse($this->itineraryService->extract($account, $mailbox, $message->getUid()));
		$response->cacheFor(24 * 60 * 60, false, true);
		return $response;
	}

	private function isSenderTrusted(Message $message): bool {
		$from = $message->getFrom();
		$first = $from->first();
		if ($first === null) {
			return false;
		}
		$email = $first->getEmail();
		if ($email === null) {
			return false;
		}
		return $this->trustedSenderService->isTrusted(
			$this->currentUserId,
			$email
		);
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

		if (empty($message->getThreadRootId())) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($this->mailManager->getThread($account, $message->getThreadRootId()));
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
	 * @TrapError
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function mdn(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		if ($message->getFlagMdnsent()) {
			return new JSONResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		try {
			$this->mailTransmission->sendMdn($account, $mailbox, $message);
			$this->mailManager->flagMessage($account, $mailbox->getName(), $message->getUid(), 'mdnsent', true);
		} catch (ServiceException $ex) {
			$this->logger->error('Sending mdn failed: ' . $ex->getMessage());
			throw $ex;
		}

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

		$client = $this->clientFactory->getClient($account);
		try {
			$response = new JSONResponse([
				'source' => $this->mailManager->getSource(
					$client,
					$account,
					$mailbox->getName(),
					$message->getUid()
				)
			]);
		} finally {
			$client->logout();
		}

		// Enable caching
		$response->cacheFor(60 * 60, false, true);

		return $response;
	}

	/**
	 * Export a whole message as an .eml file.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @return Response
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function export(int $id): Response {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$source = $this->mailManager->getSource(
				$client,
				$account,
				$mailbox->getName(),
				$message->getUid()
			);
		} finally {
			$client->logout();
		}

		return new AttachmentDownloadResponse(
			$source,
			$message->getSubject() . '.eml',
			'message/rfc822',
		);
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
	public function getHtmlBody(int $id, bool $plain = false): Response {
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

			$client = $this->clientFactory->getClient($account);
			try {
				$imapMessage = $this->mailManager->getImapMessage(
					$client,
					$account,
					$mailbox,
					$message->getUid(),
					true
				);
			} finally {
				$client->logout();
			}

			if ($plain) {
				$htmlResponse = HtmlResponse::plain(
					$imapMessage->getHtmlBody($id, true)
				);
			} else {
				$htmlResponse = HtmlResponse::withResizer(
					$imapMessage->getHtmlBody($id),
					$this->nonceManager->getNonce(),
					$this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->linkTo('mail', 'js/htmlresponse.js')
					)
				);
			}

			// Harden the default security policy
			$policy = new ContentSecurityPolicy();
			$policy->allowEvalScript(false);
			$policy->disallowScriptDomain('\'self\'');
			$policy->disallowConnectDomain('\'self\'');
			$policy->disallowFontDomain('\'self\'');
			$policy->disallowMediaDomain('\'self\'');
			$htmlResponse->setContentSecurityPolicy($policy);

			// Enable caching
			$htmlResponse->cacheFor(60 * 60, false, true);

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
	 * @NoCSRFRequired
	 * @TrapError
	 *
	 * @param int $id the message id
	 * @param string $attachmentId
	 *
	 * @return ZipResponse|JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 * @throws DoesNotExistException
	 */
	public function downloadAttachments(int $id): Response {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$attachments = $this->mailManager->getMailAttachments($account, $mailbox, $message);
		$zip = new ZipResponse($this->request, 'attachments');

		foreach ($attachments as $attachment) {
			$fileName = $attachment['name'];
			$fh = fopen("php://temp", 'r+');
			fputs($fh, $attachment['content']);
			$size = (int)$attachment['size'];
			rewind($fh);
			$zip->addResource($fh, $fileName, $size);
		}
		return $zip;
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
	 * @param int $id
	 * @param string $imapLabel
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function setTag(int $id, string $imapLabel): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$tag = $this->mailManager->getTagByImapLabel($imapLabel, $this->currentUserId);
		} catch (ClientException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->tagMessage($account, $mailbox->getName(), $message, $tag, true);
		return new JSONResponse($tag);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string $imapLabel
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function removeTag(int $id, string $imapLabel): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$tag = $this->mailManager->getTagByImapLabel($imapLabel, $this->currentUserId);
		} catch (ClientException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->tagMessage($account, $mailbox->getName(), $message, $tag, false);
		return new JSONResponse($tag);
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
	 * Determines if the content of this attachment is an image
	 *
	 * @param array $attachment
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
