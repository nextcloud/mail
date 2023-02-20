<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\DraftMessageCreatedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

class DraftsService {
	private IMailTransmission $transmission;
	private LocalMessageMapper $mapper;
	private AttachmentService $attachmentService;
	private IEventDispatcher $eventDispatcher;
	private IMAPClientFactory $clientFactory;
	private IMailManager $mailManager;
	private LoggerInterface $logger;
	private AccountService $accountService;
	private ITimeFactory $time;

	public function __construct(IMailTransmission  $transmission,
								LocalMessageMapper $mapper,
								AttachmentService  $attachmentService,
								IEventDispatcher    $eventDispatcher,
								IMAPClientFactory $clientFactory,
								IMailManager $mailManager,
								LoggerInterface $logger,
								AccountService $accountService,
								ITimeFactory $time) {
		$this->transmission = $transmission;
		$this->mapper = $mapper;
		$this->attachmentService = $attachmentService;
		$this->eventDispatcher = $eventDispatcher;
		$this->clientFactory = $clientFactory;
		$this->mailManager = $mailManager;
		$this->logger = $logger;
		$this->accountService = $accountService;
		$this->time = $time;
	}

	/**
	 * @param array $recipients
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
	 * @param string $userId
	 * @param LocalMessage $message
	 * @return void
	 */
	public function deleteMessage(string $userId, LocalMessage $message): void {
		$this->attachmentService->deleteLocalMessageAttachments($userId, $message->getId());
		$this->mapper->deleteWithRecipients($message);
	}

	/**
	 * @param Account $account
	 * @param LocalMessage $message
	 * @param array<int, string[]> $to
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function saveMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);

		// if this message has a "sent at" datestamp and the type is set as Type Outbox
		// check for a valid recipient
		if ($message->getType() === LocalMessage::TYPE_OUTGOING && empty($toRecipients)) {
			throw new ClientException('Cannot convert message to outbox message without at least one recipient');
		}

		$message = $this->mapper->saveWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if (empty($attachments)) {
			$message->setAttachments($attachments);
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
	 * @param Account $account
	 * @param LocalMessage $message
	 * @param array<int, string[]> $to
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function updateMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);

		// if this message has a "sent at" datestamp and the type is set as Type Outbox
		// check for a valid recipient
		if ($message->getType() === LocalMessage::TYPE_OUTGOING && empty($toRecipients)) {
			throw new ClientException('Cannot convert message to outbox message without at least one recipient');
		}

		$message = $this->mapper->updateWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if (empty($attachments)) {
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
		$this->eventDispatcher->dispatchTyped(new DraftMessageCreatedEvent($account, $message));
	}

	/**
	 * "Send" the message
	 *
	 * @param LocalMessage $message
	 * @param Account $account
	 * @return void
	 */
	public function sendMessage(LocalMessage $message, Account $account): void {
		try {
			$this->transmission->saveLocalDraft($account, $message);
		} catch (ClientException|ServiceException $e) {
			$this->logger->error('Could not move draft to IMAP', ['exception' => $e]);
			// Mark as failed so the message is not moved repeatedly in background
			$message->setFailed(true);
			$this->mapper->update($message);
			throw $e;
		}
		$this->attachmentService->deleteLocalMessageAttachments($account->getUserId(), $message->getId());
		$this->mapper->deleteWithRecipients($message);
	}

	/**
	 *
	 * @param int $id
	 * @param string $userId
	 * @return LocalMessage
	 * @throws DoesNotExistException
	 */
	public function getMessage(int $id, string $userId): LocalMessage {
		return $this->mapper->findById($id, $userId);
	}

	/**
	 * @return void
	 */
	public function flush() {
		$messages = $this->mapper->findDueDrafts($this->time->getTime());
		if (empty($messages)) {
			return;
		}

		$accountIds = array_unique(array_map(static function ($message) {
			return $message->getAccountId();
		}, $messages));

		$accounts = array_combine($accountIds, array_map(function ($accountId) {
			try {
				return $this->accountService->findById($accountId);
			} catch (DoesNotExistException $e) {
				// The message belongs to a deleted account
				return null;
			}
		}, $accountIds));

		foreach ($messages as $message) {
			try {
				$account = $accounts[$message->getAccountId()];
				if ($account === null) {
					// Ignore message of non-existent account
					continue;
				}
				$this->sendMessage(
					$message,
					$account,
				);
				$this->logger->debug('Draft {id} moved to IMAP', [
					'id' => $message->getId(),
				]);
			} catch (Throwable $e) {
				// Failure of one message should not stop other messages
				// Log and continue
				$this->logger->warning('Could not move draft {id} to IMAP: ' . $e->getMessage(), [
					'id' => $message->getId(),
					'exception' => $e,
				]);
			}
		}
	}
}
