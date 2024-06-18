<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Controller;

use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class MessageApiController extends OCSController {

	private ?string $userId;

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		private IUserManager $userManager,
		private AccountService $accountService,
		private AliasesService $aliasesService,
		private AttachmentService $attachmentService,
		private OutboxService $outboxService,
		private MailManager $mailManager,
		private IMAPClientFactory $clientFactory,
		private LoggerInterface $logger,
		private ITimeFactory $time,
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
	}

	/**
	 * @param int $id
	 * @return JSONResponse
	 */
	#[BruteForceProtection('mailGetMessage')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (ClientException | DoesNotExistException $e) {
			$this->logger->error('Message, Account or Mailbox not found', ['exception' => $e->getMessage()]);
			return new JSONResponse(null, Http::STATUS_FORBIDDEN);
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
			return new JSONResponse(null, Http::STATUS_NOT_FOUND);
		} finally {
			$client->logout();
		}

		$messageContent = new AttachmentDownloadResponse(
			$source,
			$message->getSubject() . '.eml',
			'message/rfc822',
		);

		return new JSONResponse($messageContent->render(), Http::STATUS_OK);
	}
}
