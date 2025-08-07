<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use Exception;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IDkimService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\SmimeData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Service\SnoozeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ZipResponse;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;
use function array_map;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
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
	private SmimeService $smimeService;
	private IMAPClientFactory $clientFactory;
	private IDkimService $dkimService;
	private IUserPreferences $preferences;
	private SnoozeService $snoozeService;
	private AiIntegrationsService $aiIntegrationService;

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
		SmimeService $smimeService,
		IMAPClientFactory $clientFactory,
		IDkimService $dkimService,
		IUserPreferences $preferences,
		SnoozeService $snoozeService,
		AiIntegrationsService $aiIntegrationService) {
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
		$this->smimeService = $smimeService;
		$this->clientFactory = $clientFactory;
		$this->dkimService = $dkimService;
		$this->preferences = $preferences;
		$this->snoozeService = $snoozeService;
		$this->aiIntegrationService = $aiIntegrationService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $mailboxId
	 * @param int $cursor
	 * @param string $filter
	 * @param int|null $limit
	 * @param string $view returns messages in requested view ('singleton' or 'threaded')
	 * @param string|null $v Cache buster version to guarantee unique urls (will trigger HTTP caching if set)
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function index(int $mailboxId,
		?int $cursor = null,
		?string $filter = null,
		?int $limit = null,
		?string $view = null,
		?string $v = null): JSONResponse {
		try {
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $mailboxId);
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->logger->debug("loading messages of mailbox <$mailboxId>");
		$sort = $this->preferences->getPreference($this->currentUserId, 'sort-order', 'newest') === 'newest' ? IMailSearch::ORDER_NEWEST_FIRST : IMailSearch::ORDER_OLDEST_FIRST;

		$view = $view === 'singleton' ? IMailSearch::VIEW_SINGLETON : IMailSearch::VIEW_THREADED;

		$messages = $this->mailSearch->findMessages(
			$account,
			$mailbox,
			$sort,
			$filter === '' ? null : $filter,
			$cursor,
			$limit,
			$this->currentUserId,
			$view
		);

		$response = new JSONResponse($messages);
		if ($v !== null && $v !== '') {
			$response->cacheFor(7 * 24 * 3600, false, true);
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
			$imapMessage = $this->mailManager->getImapMessage(
				$client,
				$account,
				$mailbox,
				$message->getUid(), true
			);
			$json = $imapMessage->getFullMessage($id);
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

		$smimeData = new SmimeData();
		$smimeData->setIsEncrypted($message->isEncrypted() || $imapMessage->isEncrypted());
		if ($imapMessage->isSigned()) {
			$smimeData->setIsSigned(true);
			$smimeData->setSignatureIsValid($imapMessage->isSignatureValid());
		}
		$json['smime'] = $smimeData;

		$dkimResult = $this->dkimService->getCached($account, $mailbox, $message->getUid());
		if (is_bool($dkimResult)) {
			$json['dkimValid'] = $dkimResult;
		}

		$response = new JSONResponse($json);

		// Enable caching
		$response->cacheFor(60 * 60, false, true);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
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

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @return JSONResponse
	 */
	public function getDkim(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$response = new JSONResponse(['valid' => $this->dkimService->validate($account, $mailbox, $message->getUid())]);
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
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @param int $destFolderId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @param int $unixTimestamp
	 * @param int $destMailboxId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function snooze(int $id, int $unixTimestamp, int $destMailboxId): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$dstMailbox = $this->mailManager->getMailbox($this->currentUserId, $destMailboxId);
			$srcAccount = $this->accountService->find($this->currentUserId, $srcMailbox->getAccountId());
			$dstAccount = $this->accountService->find($this->currentUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->snoozeMessage($message, $unixTimestamp, $srcAccount, $srcMailbox, $dstAccount, $dstMailbox);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function unSnooze(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->unSnoozeMessage($message, $this->currentUserId);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @return Response
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @param bool $plain do not inject scripts if true (default=false)
	 *
	 * @return HtmlResponse|TemplateResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
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
				$html = $this->mailManager->getImapMessage(
					$client,
					$account,
					$mailbox,
					$message->getUid(),
					true
				)->getHtmlBody(
					$id
				);
			} finally {
				$client->logout();
			}

			$htmlResponse = $plain
				? HtmlResponse::plain($html)
				: HtmlResponse::withResizer(
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
	 *
	 * @param int $id
	 * @param string $attachmentId
	 *
	 * @return Response
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function downloadAttachment(int $id,
		string $attachmentId): Response {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$attachment = $this->mailManager->getMailAttachment(
			$account,
			$mailbox,
			$message,
			$attachmentId,
		);

		// Body party and embedded messages do not have a name
		if ($attachment->getName() === null) {
			return new AttachmentDownloadResponse(
				$attachment->getContent(),
				$this->l10n->t('Embedded message %s', [
					$attachmentId,
				]) . '.eml',
				$attachment->getType()
			);
		}
		return new AttachmentDownloadResponse(
			$attachment->getContent(),
			$attachment->getName(),
			$attachment->getType()
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id the message id
	 *
	 * @return ZipResponse|JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 * @throws DoesNotExistException
	 */
	#[TrapError]
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
			$fileName = $attachment->getName();
			$fh = fopen('php://temp', 'r+');
			fputs($fh, $attachment->getContent());
			$size = $attachment->getSize();
			rewind($fh);
			$zip->addResource($fh, $fileName, $size);
		}
		return $zip;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $attachmentId
	 * @param string $targetPath
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws GenericFileException
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	#[TrapError]
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

		/** @var Attachment[] $attachments */
		$attachments = [];
		if ($attachmentId === '0') {
			$attachments = $this->mailManager->getMailAttachments(
				$account,
				$mailbox,
				$message,
			);
		} else {
			$attachments[] = $this->mailManager->getMailAttachment(
				$account,
				$mailbox,
				$message,
				$attachmentId,
			);
		}

		foreach ($attachments as $attachment) {
			$fileName = $attachment->getName() ?? $this->l10n->t('Embedded message %s', [
				$attachment->getId(),
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
			$newFile->putContent($attachment->getContent());
		}
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param array $flags
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @param string $imapLabel
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 * @param string $imapLabel
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 *
	 * @param int $id
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 * @NoAdminRequired
	 *
	 * @param int $messageId
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function smartReply(int $messageId):JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $messageId);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		try {
			$replies = array_values($this->aiIntegrationService->getSmartReply($account, $mailbox, $message, $this->currentUserId));
		} catch (ServiceException $e) {
			$this->logger->error('Smart reply failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return new JSONResponse([], Http::STATUS_NO_CONTENT);
		}
		return new JSONResponse($replies);

	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $messageId
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function needsTranslation(int $messageId): JSONResponse {
		if ($this->currentUserId === null) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $messageId);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		if (!$this->aiIntegrationService->isLlmProcessingEnabled()) {
			$response = new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}

		try {
			$requiresTranslation = $this->aiIntegrationService->requiresTranslation(
				$account,
				$mailbox,
				$message,
				$this->currentUserId
			);
			$response = new JSONResponse(['requiresTranslation' => $requiresTranslation === true]);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		} catch (ServiceException $e) {
			$this->logger->error('Translation check failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return new JSONResponse([], Http::STATUS_NO_CONTENT);
		}
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
