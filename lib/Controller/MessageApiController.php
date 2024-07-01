<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IDkimService;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\SmimeData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\TrustedSenderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class MessageApiController extends OCSController {

	private ?string $userId;

	public function __construct(
		string $appName,
		?string $userId,
		IRequest $request,
		private AccountService $accountService,
		private AliasesService $aliasesService,
		private AttachmentService $attachmentService,
		private OutboxService $outboxService,
		private MailSearch $mailSearch,
		private MailManager $mailManager,
		private IMAPClientFactory $clientFactory,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private IURLGenerator $urlGenerator,
		private IMimeTypeDetector $mimeTypeDetector,
		private IDkimService $dkimService,
		private ItineraryService $itineraryService,
		private TrustedSenderService $trustedSenderService,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * @param int $id
	 * @return DataResponse
	 */
	#[BruteForceProtection('mailGetMessage')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(int $id): DataResponse {
		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (ClientException | DoesNotExistException $e) {
			$this->logger->error('Message, Account or Mailbox not found', ['exception' => $e->getMessage()]);
			return new DataResponse($e, Http::STATUS_FORBIDDEN);
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
		} catch (ServiceException $e) {
			$this->logger->error('Message could not be loaded', ['exception' => $e->getMessage()]);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
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
		$json['id'] = $message->getId();
		$json['isSenderTrusted'] = $this->trustedSenderService->isSenderTrusted($this->userId, $message);

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

		return new DataResponse($json, Http::STATUS_OK);
	}

	#[BruteForceProtection('mailGetRawMessage')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function raw(int $id): DataResponse {
		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (ClientException | DoesNotExistException $e) {
			$this->logger->error('Message, Account or Mailbox not found', ['exception' => $e->getMessage()]);
			return new DataResponse($e, Http::STATUS_FORBIDDEN);
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$source = $this->mailManager->getSource(
				$client,
				$account,
				$mailbox->getName(),
				$message->getUid()
			);
		} catch (ServiceException $e) {
			$this->logger->error('Message not found on IMAP or mail server went away', ['exception' => $e->getMessage()]);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		} finally {
			$client->logout();
		}

		return new DataResponse($source, Http::STATUS_OK);
	}

	/**
	 * @param int $id
	 * @param array $attachment
	 *
	 * @return array
	 */
	private function enrichDownloadUrl(int $id, array $attachment) {
		$downloadUrl = $this->urlGenerator->linkToRoute('mail.messageApi.downloadAttachment',
			[
				'id' => $id,
				'attachmentId' => $attachment['id'],
			]);
		$downloadUrl = $this->urlGenerator->getAbsoluteURL($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		return $attachment;
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[TrapError]
	public function downloadAttachment(int $id,
		string $attachmentId): Response {
		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse($e, Http::STATUS_FORBIDDEN);
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
}
