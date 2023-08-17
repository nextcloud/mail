<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Johannes Merkel <mail@johannesgge.de>
 *
 * @author Johannes Merkel <mail@johannesgge.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\MessageSnooze;
use OCA\Mail\Db\MessageSnoozeMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Throwable;

class SnoozeService {

	public function __construct(
		private ITimeFactory $time,
		private LoggerInterface $logger,
		private IMAPClientFactory $clientFactory,
		private MessageMapper $messageMapper,
		private MessageSnoozeMapper $messageSnoozeMapper,
		private MailAccountMapper $accountMapper,
		private MailboxMapper $mailboxMapper,
		private IMailManager $mailManager,
		private ThreadMapper $threadMapper
	) {

	}

	/**
	 * Wakes snoozed messages (move back to INBOX and delete DB Entry)
	 *
	 * @return void
	 */
	public function wakeMessages(): void {
		$accounts = $this->accountMapper->getAllAccounts();
		foreach ($accounts as $account) {
			$account = new Account($account);

			try {
				$this->wakeMessagesByAccount($account);
			} catch (ServiceException|ClientException $e) {
				$this->logger->error('Could not wake messages', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
					'snoozeMailboxId' => $account->getMailAccount()->getSnoozeMailboxId(),
				]);
			}
		}
	}

	/**
	 * @param Message $message
	 * @param int $unixTimestamp
	 * @param Account $srcAccount
	 * @param Mailbox $srcMailbox
	 * @param Account $dstAccount
	 * @param Mailbox $dstMailbox
	 *
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function snoozeMessage(
		Message $message,
		int $unixTimestamp,
		Account $srcAccount,
		Mailbox $srcMailbox,
		Account $dstAccount,
		Mailbox $dstMailbox
	): void {
		$this->snoozeMessageDB($message, $unixTimestamp);

		try {
			$this->mailManager->moveMessage(
				$srcAccount,
				$srcMailbox->getName(),
				$message->getUid(),
				$dstAccount,
				$dstMailbox->getName()
			);
		} catch (Throwable $e) {
			$this->unSnoozeMessageDB($message);
			throw $e;
		}
	}

	/**
	 * @param Message $selectedMessage
	 * @param int $unixTimestamp
	 * @param Account $srcAccount
	 * @param Mailbox $srcMailbox
	 * @param Account $dstAccount
	 * @param Mailbox $dstMailbox
	 *
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function snoozeThread(
		Message $selectedMessage,
		int $unixTimestamp,
		Account $srcAccount,
		Mailbox $srcMailbox,
		Account $dstAccount,
		Mailbox $dstMailbox
	):void {
		$this->snoozeThreadDB($selectedMessage, $unixTimestamp);

		try {
			$this->mailManager->moveThread(
				$srcAccount,
				$srcMailbox,
				$dstAccount,
				$dstMailbox,
				$selectedMessage->getThreadRootId()
			);
		} catch (Throwable $e) {
			$this->unSnoozeThreadDB($selectedMessage);
			throw $e;
		}

	}


	/**
	 * Adds a DB entry for the message with a wake timestamp
	 *
	 * @param Message $message
	 * @param int $unixTimestamp
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function snoozeMessageDB(Message $message, int $unixTimestamp): void {
		$snooze = new MessageSnooze();
		$snooze->setMessageId($message->getMessageId());
		$snooze->setSnoozedUntil($unixTimestamp);
		try {
			$this->messageSnoozeMapper->insert($snooze);
		} catch(Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$messageId = $message->getMessageId();
				if($messageId === null) {
					throw $e;
				}
				$this->messageSnoozeMapper->deleteByMessageIds(array($messageId));
				$this->messageSnoozeMapper->insert($snooze);
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Removes the DB entry for the message
	 *
	 * @param Message $message
	 * @return void
	 */
	public function unSnoozeMessageDB(Message $message): void {
		$messageId = $message->getMessageId();
		if($messageId !== null) {
			$this->messageSnoozeMapper->deleteByMessageIds(array($messageId));
		}
	}

	/**
	 * Adds a DB entry for the messages with a wake timestamp
	 *
	 * @param Message $selectedMessage
	 * @param int $unixTimestamp
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function snoozeThreadDB(Message $selectedMessage, int $unixTimestamp): void {
		$messages = $this->threadMapper->findMessageIdsByThreadRoot(
			$selectedMessage->getThreadRootId()
		);

		foreach ($messages as $message) {
			$snooze = new MessageSnooze();
			$snooze->setMessageId($message['messageId']);
			$snooze->setSnoozedUntil($unixTimestamp);
			try {
				$this->messageSnoozeMapper->insert($snooze);
			} catch(Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					$messageId = $message['messageId'];
					if($messageId === null) {
						throw $e;
					}
					$this->messageSnoozeMapper->deleteByMessageIds(array($messageId));
					$this->messageSnoozeMapper->insert($snooze);
				} else {
					throw $e;
				}
			}
		}
	}

	/**
	 * Removes DB entry for the messages
	 *
	 * @param Message $selectedMessage
	 * @return void
	 */
	public function unSnoozeThreadDB(Message $selectedMessage): void {
		$messages = $this->threadMapper->findMessageIdsByThreadRoot(
			$selectedMessage->getThreadRootId()
		);

		$messageIdsToDelete = [];
		foreach ($messages as $message) {
			$messageIdsToDelete[] = $message['messageId'];
		}
		if(count($messageIdsToDelete) > 0) {
			$this->messageSnoozeMapper->deleteByMessageIds($messageIdsToDelete);
		}
	}


	/**
	 * @throws ServiceException
	 */
	private function wakeMessagesByAccount(Account $account): void {
		$snoozeMailboxId = $account->getMailAccount()->getSnoozeMailboxId();
		if ($snoozeMailboxId === null) {
			return;
		}

		try {
			$snoozeMailbox = $this->mailboxMapper->findById($snoozeMailboxId);
		} catch (DoesNotExistException $e) {
			return;
		}

		$now = $this->time->getTime();
		$messages = $this->messageMapper->findMessagesToUnSnooze(
			$snoozeMailboxId,
			$now,
		);

		if (count($messages) === 0) {
			return;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$messageIdsToDelete = [];
			foreach ($messages as $message) {
				$this->mailManager->moveMessage(
					$account,
					$snoozeMailbox->getName(),
					$message->getUid(),
					$account,
					'INBOX'
				);

				//TODO mark message as unread?

				$messageId = $message->getMessageId();
				if($messageId !== null) {
					$messageIdsToDelete[] = $messageId;
				}

			}
			if(count($messageIdsToDelete) > 0) {
				$this->messageSnoozeMapper->deleteByMessageIds($messageIdsToDelete);
			}
		} finally {
			$client->logout();
		}
	}

}
