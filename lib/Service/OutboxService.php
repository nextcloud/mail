<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\OutboxMessageCreatedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Send\Chain;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use Throwable;

class OutboxService {


	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(
		private readonly \OCA\Mail\Db\LocalMessageMapper $mapper,
		private readonly \OCA\Mail\Service\Attachment\AttachmentService $attachmentService,
		IEventDispatcher $eventDispatcher,
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $clientFactory,
		private readonly \OCA\Mail\Contracts\IMailManager $mailManager,
		private readonly \OCA\Mail\Service\AccountService $accountService,
		ITimeFactory $timeFactory,
		private readonly \Psr\Log\LoggerInterface $logger,
		private readonly Chain $sendChain,
	) {
		$this->eventDispatcher = $eventDispatcher;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param array<int, array{email: string, label?: string}> $recipients
	 * @return Recipient[]
	 */
	private static function convertToRecipient(array $recipients, int $type): array {
		return array_map(static function (array $recipient) use ($type): \OCA\Mail\Db\Recipient {
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
		return $this->sendChain->process($account, $message);
	}

	/**
	 * @param array<int, array{email: string, label?: string}> $to
	 * @param array<int, array{email: string, label?: string}> $cc
	 * @param array<int, array{email: string, label?: string}> $bcc
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

		$client = $this->clientFactory->getClient($account);
		try {
			$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments, $client);
		} finally {
			$client->logout();
		}

		$message->setAttachments($this->attachmentService->saveLocalMessageAttachments($account->getUserId(), $message->getId(), $attachmentIds));
		return $message;
	}

	/**
	 * @param array<int, array{email: string, label?: string}> $to
	 * @param array<int, array{email: string, label?: string}> $cc
	 * @param array<int, array{email: string, label?: string}> $bcc
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

		$client = $this->clientFactory->getClient($account);
		try {
			$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments, $client);
		} finally {
			$client->logout();
		}
		$message->setAttachments($this->attachmentService->updateLocalMessageAttachments($account->getUserId(), $message, $attachmentIds));
		return $message;
	}

	public function handleDraft(Account $account, int $draftId): void {
		$message = $this->mailManager->getMessage($account->getUserId(), $draftId);
		$this->eventDispatcher->dispatch(
			OutboxMessageCreatedEvent::class,
			new OutboxMessageCreatedEvent($account, $message)
		);
	}

	public function flush(): void {
		$messages = $this->mapper->findDue(
			$this->timeFactory->getTime()
		);

		if ($messages === []) {
			return;
		}

		$accountIds = array_unique(array_map(static fn ($message) => $message->getAccountId(), $messages));

		$accounts = array_combine($accountIds, array_map(function (int $accountId): ?\OCA\Mail\Account {
			try {
				return $this->accountService->findById($accountId);
			} catch (DoesNotExistException) {
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
			try {
				$this->sendChain->process($account, $message);
				$this->logger->debug('Outbox message {id} sent', [
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
