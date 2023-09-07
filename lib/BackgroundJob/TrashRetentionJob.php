<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class TrashRetentionJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private IMAPClientFactory $clientFactory,
		private MessageMapper $messageMapper,
		private MessageRetentionMapper $messageRetentionMapper,
		private MailAccountMapper $accountMapper,
		private MailboxMapper $mailboxMapper,
		private IMailManager $mailManager,
	) {
		parent::__construct($time);

		$this->setInterval(24 * 3600);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @inheritDoc
	 */
	public function run($argument) {
		$accounts = $this->accountMapper->getAllAccounts();
		foreach ($accounts as $account) {
			$account = new Account($account);

			$retentionDays = $account->getMailAccount()->getTrashRetentionDays();
			if ($retentionDays === null || $retentionDays <= 0) {
				continue;
			}

			$retentionSeconds = $retentionDays * 24 * 3600;

			try {
				$this->cleanTrash($account, $retentionSeconds);
			} catch (ServiceException|ClientException $e) {
				$this->logger->error('Could not clean trash mailbox', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
					'trashMailboxId' => $account->getMailAccount()->getTrashMailboxId(),
				]);
			}
		}

	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	private function cleanTrash(Account $account, int $retentionSeconds): void {
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		if ($trashMailboxId === null) {
			return;
		}

		try {
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			return;
		}

		$now = $this->time->getTime();
		$messages = $this->messageMapper->findMessagesKnownSinceBefore(
			$trashMailboxId,
			$now - $retentionSeconds,
		);

		if (count($messages) === 0) {
			return;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			foreach ($messages as $message) {
				$this->mailManager->deleteMessageWithClient(
					$account,
					$trashMailbox,
					$message->getUid(),
					$client,
				);
				$this->messageRetentionMapper->deleteByMailboxIdAndUid(
					$message->getMailboxId(),
					$message->getUid(),
				);
			}
		} finally {
			$client->logout();
		}
	}
}
