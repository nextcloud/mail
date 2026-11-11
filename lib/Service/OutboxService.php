<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\OutboxMessageCreatedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\SendInProgressException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Send\Chain;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

class OutboxService {
	/**
	 * How long (seconds) a send request waits for a concurrent send of the same
	 * message (normally the OutboxWorkerJob) before giving up.
	 */
	public const CONCURRENT_SEND_WAIT = 60;


	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(
		private LocalMessageMapper $mapper,
		private AttachmentService $attachmentService,
		IEventDispatcher $eventDispatcher,
		private MailManager $mailManager,
		private AccountService $accountService,
		ITimeFactory $timeFactory,
		private LoggerInterface $logger,
		private Chain $sendChain,
	) {
		$this->eventDispatcher = $eventDispatcher;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param array<int, array{email: string, label?: string}> $recipients
	 * @param int $type
	 * @return Recipient[]
	 */
	private static function convertToRecipient(array $recipients, int $type): array {
		return array_map(static function ($recipient) use ($type) {
			$r = new Recipient();
			$r->setType($type);
			$r->setLabel($recipient['label'] ?? $recipient['email']);
			$r->setEmail($recipient['email']);
			return $r;
		}, $recipients);
	}

	/**
	 * @return LocalMessage[]
	 */
	public function getMessages(string $userId): array {
		return $this->mapper->getAllForUser($userId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getMessage(int $id, string $userId): LocalMessage {
		return $this->mapper->findById($id, $userId, LocalMessage::TYPE_OUTGOING);
	}

	/**
	 * @param string $userId
	 * @param LocalMessage $message
	 * @return void
	 */
	public function deleteMessage(string $userId, LocalMessage $message): void {
		$this->attachmentService->deleteLocalMessageAttachments($userId, $message->getId());
		$this->mapper->deleteWithRecipients($message);
	}

	/**
	 * @throws Throwable
	 * @throws Exception
	 * @throws ServiceException
	 */
	public function sendMessage(LocalMessage $message, Account $account): LocalMessage {
		try {
			return $this->sendChain->process($account, $message);
		} catch (SendInProgressException $e) {
			// Another process (normally the OutboxWorkerJob) picked this message up a
			// moment ago. Wait for it and report its outcome instead of producing a
			// second copy of the message.
			return $this->awaitConcurrentSend($message, $account, $e);
		}
	}

	/**
	 * Wait for a concurrent send of the same message to finish.
	 *
	 * Returns the message flagged as processed when the other process sent and
	 * removed it, sends it when the other process gave up and left it in the
	 * outbox, and rethrows when the other process is still busy after the
	 * waiting period (the OutboxWorkerJob will take care of the message).
	 *
	 * @throws SendInProgressException
	 * @throws Throwable
	 */
	private function awaitConcurrentSend(LocalMessage $message, Account $account, SendInProgressException $e): LocalMessage {
		$deadline = $this->timeFactory->getTime() + self::CONCURRENT_SEND_WAIT;
		while ($this->timeFactory->getTime() < $deadline) {
			sleep(1);
			if ($this->sendChain->isSending($message)) {
				continue;
			}
			try {
				$current = $this->mapper->findById($message->getId(), $account->getUserId(), LocalMessage::TYPE_OUTGOING);
			} catch (DoesNotExistException $notFound) {
				// Sent and removed from the outbox by the other process
				$this->logger->debug('Outbox message {id} was sent by a concurrent process', [
					'id' => $message->getId(),
				]);
				$message->setStatus(LocalMessage::STATUS_PROCESSED);
				return $message;
			}
			// The other process did not finish the job (e.g. SMTP error): try again with the fresh state
			return $this->sendChain->process($account, $current);
		}
		throw $e;
	}

	/**
	 * @param Account $account
	 * @param LocalMessage $message
	 * @param array<int, array{email: string, label?: string}> $to
	 * @param array<int, array{email: string, label?: string}> $cc
	 * @param array<int, array{email: string, label?: string}> $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function saveMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);
		$message = $this->mapper->saveWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if ($attachments === []) {
			$message->setAttachments([]);
			return $message;
		}

		$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments);

		$message->setAttachments($this->attachmentService->saveLocalMessageAttachments($account->getUserId(), $message->getId(), $attachmentIds));
		return $message;
	}

	/**
	 * @param Account $account
	 * @param LocalMessage $message
	 * @param array<int, array{email: string, label?: string}> $to
	 * @param array<int, array{email: string, label?: string}> $cc
	 * @param array<int, array{email: string, label?: string}> $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function updateMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);

		$message = $this->mapper->updateWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if ($attachments === []) {
			$message->setAttachments($this->attachmentService->updateLocalMessageAttachments($account->getUserId(), $message, []));
			return $message;
		}

		$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments);
		$message->setAttachments($this->attachmentService->updateLocalMessageAttachments($account->getUserId(), $message, $attachmentIds));
		return $message;
	}

	/**
	 * @param Account $account
	 * @param int $draftId
	 * @return void
	 */
	public function handleDraft(Account $account, int $draftId): void {
		$message = $this->mailManager->getMessage($account->getUserId(), $draftId);
		$this->eventDispatcher->dispatch(
			OutboxMessageCreatedEvent::class,
			new OutboxMessageCreatedEvent($account, $message)
		);
	}

	/**
	 * @return void
	 */
	public function flush(): void {
		$messages = $this->mapper->findDue(
			$this->timeFactory->getTime()
		);

		if ($messages === []) {
			return;
		}

		$accountIds = array_unique(array_map(static fn ($message) => $message->getAccountId(), $messages));

		$accounts = array_combine($accountIds, array_map(function ($accountId) {
			try {
				return $this->accountService->findById($accountId);
			} catch (DoesNotExistException $e) {
				// The message belongs to a deleted account

				return null;
			}
		}, $accountIds));

		foreach ($messages as $message) {
			$account = $accounts[$message->getAccountId()];
			if ($account === null) {
				// Ignore message of non-existent account
				continue;
			}

			if ($message->getStatus() === LocalMessage::STATUS_ERROR) {
				// Skip messages with status error
				continue;
			}

			try {
				$this->sendChain->process($account, $message);
				$this->logger->debug('Outbox message {id} sent', [
					'id' => $message->getId(),
				]);
			} catch (SendInProgressException $e) {
				// The web client is sending this message right now, nothing to do
				$this->logger->debug('Outbox message {id} is being sent by another process, skipping', [
					'id' => $message->getId(),
				]);
			} catch (Throwable $e) {
				// Failure of one message should not stop sending other messages
				// Log and continue
				$this->logger->warning('Could not send outbox message {id}: ' . $e->getMessage(), [
					'id' => $message->getId(),
					'exception' => $e,
				]);
			}
		}
	}

	public function convertDraft(LocalMessage $draftMessage, int $sendAt): LocalMessage {
		if (empty($draftMessage->getRecipients())) {
			throw new ClientException('Cannot convert message to outbox message without at least one recipient');
		}
		$outboxMessage = clone $draftMessage;
		$outboxMessage->setType(LocalMessage::TYPE_OUTGOING);
		$outboxMessage->setSendAt($sendAt);
		return $this->mapper->update($outboxMessage);
	}
}
