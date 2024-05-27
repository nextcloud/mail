<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Controller;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IRequest;
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
		private LoggerInterface $logger,
		private ITimeFactory $time,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	#[UserRateLimit(limit: 5, period: 100)]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function send(
		int $accountId,
		string $fromEmail,
		string $subject,
		string $body,
		bool $isHtml,
		array $to,
		array $cc = [],
		array $bcc = [],
		array $references = [],
	): DataResponse {
		if($this->userId === null) {
			return new DataResponse('', Http::STATUS_FORBIDDEN);
		}

		try {
			$mailAccount = $this->accountService->find($this->userId, $accountId);
		} catch (ClientException $e) {
			$this->logger->error("Mail account #$accountId not found", ['exception' => $e]);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		if($fromEmail !== $mailAccount->getEmail()) {
			try {
				$alias = $this->aliasesService->findByAliasAndUserId($fromEmail, $this->userId);
			} catch (DoesNotExistException $e) {
				$this->logger->error("Alias $fromEmail for mail account $accountId not found", ['exception' => $e]);
				// Cannot send from this email as it is not configured as an alias
				return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}
		}

		$attachments = $this->request->getUploadedFile('attachments');
		$numberOfAttachments = count($attachments['name'] ?? []);

		$localAttachments = [];
		$messageAttachments = [];
		$attachmentErrors = false;
		while ($numberOfAttachments > 0) {
			$numberOfAttachments--;
			$filedata = [
				'name' => $attachments['name'][$numberOfAttachments],
				'type' => $attachments['type'][$numberOfAttachments],
				'size' => $attachments['size'][$numberOfAttachments],
				'tmp_name' => $attachments['tmp_name'][$numberOfAttachments],
			];
			$file = new UploadedFile($filedata);
			try {
				$localAttachment = $this->attachmentService->addFile($this->userId, $file);
				$messageAttachments[] = $localAttachment;
				$localAttachments[] = ['type' => 'local', 'id' => $localAttachment->getId()];
			} catch (UploadException $e) {
				$this->logger->error('Could not convert attachment to local attachment', ['excpetion' => $e]);
				$attachmentErrors = true;
			}
		}

		if ($attachmentErrors) {
			foreach ($localAttachments as $localAttachment) {
				// Handle possible dangling local attachments
				$this->attachmentService->deleteAttachment($this->userId, $localAttachment['id']);
			}
			return new DataResponse('Could not handle attachments', Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($accountId);
		$message->setSubject($subject);
		$message->setBody($body);
		$message->setEditorBody($body);
		$message->setHtml($isHtml);
		$message->setSendAt($this->time->getTime());
		$message->setType(LocalMessage::TYPE_OUTGOING);

		if(isset($alias)) {
			$message->setAliasId($alias->getId());
		}
		if(!empty($references)) {
			$message->setInReplyToMessageId($references[0]); // needs DB changes I guess
		}
		if(!empty($attachments)) {
			$message->setAttachments($messageAttachments);
		}

		$localMessage = $this->outboxService->saveMessage($mailAccount, $message, $to, $cc, $bcc, $localAttachments);
		try {
			$localMessage = $this->outboxService->sendMessage($localMessage, $mailAccount);
		} catch (ServiceException $e) {
			$this->logger->error('Could not send message', ['exception' => $e]);
			return new DataResponse($e, Http::STATUS_BAD_REQUEST);
		} catch (Exception | \Throwable $e) {
			$this->logger->error('Could not send message', ['exception' => $e]);
			return new DataResponse($e, Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return match ($localMessage->getStatus()) {
			LocalMessage::STATUS_PROCESSED => new DataResponse('Success', Http::STATUS_OK),
			LocalMessage::STATUS_NO_SENT_MAILBOX => new DataResponse('Configuration error: Cannot send message without sent mailbox.', Http::STATUS_FORBIDDEN),
			LocalMessage::STATUS_SMPT_SEND_FAIL => new DataResponse('An SMTP error occured, please check your mail server logs.', Http::STATUS_INTERNAL_SERVER_ERROR),
			LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL => new DataResponse('Email was sent but could not be copied to sent mailbox.', Http::STATUS_ACCEPTED),
			default => new DataResponse('An error occured, please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR),
		};
	}
}
