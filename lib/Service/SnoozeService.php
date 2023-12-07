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
		private AccountService $accountService,
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
		$newUid = $this->mailManager->moveMessage(
			$srcAccount,
			$srcMailbox->getName(),
			$message->getUid(),
			$dstAccount,
			$dstMailbox->getName()
		);
		$snoozedMessage = clone $message;
		$snoozedMessage->setMailboxId($dstMailbox->getId());
		$snoozedMessage->setUid($newUid);
		$this->snoozeMessageDB($snoozedMessage, $unixTimestamp, $srcMailbox);
	}

	/**
	 * @param Message $message
	 * @param string|null $currentUserId
	 * @return void
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function unSnoozeMessage(
		Message $message,
		?string $currentUserId,
	): void {
		$snoozedMailbox = $this->mailManager->getMailbox($currentUserId, $message->getMailboxId());
		$srcAccount = $this->accountService->find($currentUserId, $snoozedMailbox->getAccountId());

		$originalMailboxId = $this->messageSnoozeMapper->getSrcMailboxId(
			$message->getMailboxId(),
			$message->getUid(),
		);

		$originalMailboxName = 'INBOX';

		if ($originalMailboxId !== null) {
			try {
				$originalMailbox = $this->mailboxMapper->findById($originalMailboxId);
				$originalMailboxName = $originalMailbox->getName();
			} catch (DoesNotExistException $e) {
				// Could not find mailbox, moving back to INBOX
			}
		}

		$this->mailManager->moveMessage(
			$srcAccount,
			$snoozedMailbox->getName(),
			$message->getUid(),
			$srcAccount,
			$originalMailboxName
		);

		$this->messageSnoozeMapper->deleteByMailboxIdAndUid(
			$message->getMailboxId(),
			$message->getUid(),
		);
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
	): void {
		$newUids = $this->mailManager->moveThread(
			$srcAccount,
			$srcMailbox,
			$dstAccount,
			$dstMailbox,
			$selectedMessage->getThreadRootId()
		);
		$this->snoozeThreadDB($newUids, $dstMailbox, $unixTimestamp, $srcMailbox);
	}

	/**
	 * @param Message $selectedMessage
	 * @param string|null $currentUserId
	 * @return void
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function unSnoozeThread(
		Message $selectedMessage,
		?string $currentUserId,
	):void {
		$snoozedMailbox = $this->mailManager->getMailbox($currentUserId, $selectedMessage->getMailboxId());
		$srcAccount = $this->accountService->find($currentUserId, $snoozedMailbox->getAccountId());

		$originalMailboxId = $this->messageSnoozeMapper->getSrcMailboxId(
			$selectedMessage->getMailboxId(),
			$selectedMessage->getUid(),
		);

		$originalMailboxName = 'INBOX';

		if ($originalMailboxId !== null) {
			try {
				$originalMailbox = $this->mailboxMapper->findById($originalMailboxId);
				$originalMailboxName = $originalMailbox->getName();
			} catch (DoesNotExistException $e) {
				// Could not find mailbox, moving back to INBOX
			}
		}

		$messages = $this->messageMapper->findThread($srcAccount, $selectedMessage->getThreadRootId());

		foreach ($messages as $message) {
			$this->mailManager->moveMessage(
				$srcAccount,
				$snoozedMailbox->getName(),
				$message->getUid(),
				$srcAccount,
				$originalMailboxName
			);

			$this->messageSnoozeMapper->deleteByMailboxIdAndUid(
				$message->getMailboxId(),
				$message->getUid(),
			);
		}
	}


	/**
	 * Adds a DB entry for the message with a wake timestamp
	 *
	 * @param Message $message
	 * @param int $unixTimestamp
	 * @param Mailbox $srcMailbox
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @throws ServiceException
	 */
	public function snoozeMessageDB(Message $message, int $unixTimestamp, Mailbox $srcMailbox): void {
		$snooze = new MessageSnooze();
		$snooze->setMailboxId($message->getMailboxId());
		$snooze->setUid($message->getUid());
		$snooze->setSnoozedUntil($unixTimestamp);
		$snooze->setSrcMailboxId($srcMailbox->getId());

		$this->messageSnoozeMapper->insert($snooze);
	}

	/**
	 * Adds a DB entry for the messages with a wake timestamp
	 *
	 * @param array<int> $uids
	 * @param int $unixTimestamp
	 * @param Mailbox $srcMailbox
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function snoozeThreadDB(array $uids, Mailbox $dstMailbox, int $unixTimestamp, Mailbox $srcMailbox): void {
		foreach ($uids as $uid) {
			$snooze = new MessageSnooze();
			$snooze->setMailboxId($dstMailbox->getId());
			$snooze->setUid($uid);
			$snooze->setSnoozedUntil($unixTimestamp);
			$snooze->setSrcMailboxId($srcMailbox->getId());

			$this->messageSnoozeMapper->insert($snooze);
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
			foreach ($messages as $message) {
				$srcMailboxId = $this->messageSnoozeMapper->getSrcMailboxId(
					$message->getMailboxId(),
					$message->getUid(),
				);

				$srcMailboxName = 'INBOX';

				if ($srcMailboxId !== null) {
					try {
						$srcMailbox = $this->mailboxMapper->findById($srcMailboxId);
						$srcMailboxName = $srcMailbox->getName();
					} catch (DoesNotExistException $e) {
						// Could not find mailbox, moving back to INBOX
					}
				}

				$this->mailManager->flagMessage($account, $snoozeMailbox->getName(), $message->getUid(), 'seen', false);

				$this->mailManager->moveMessage(
					$account,
					$snoozeMailbox->getName(),
					$message->getUid(),
					$account,
					$srcMailboxName
				);

				$this->messageSnoozeMapper->deleteByMailboxIdAndUid(
					$message->getMailboxId(),
					$message->getUid(),
				);
			}
		} finally {
			$client->logout();
		}
	}

}
