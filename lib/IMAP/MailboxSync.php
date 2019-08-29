<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\IMAP;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Folder;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;

class MailboxSync {

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var ILogger */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								FolderMapper $folderMapper,
								MailAccountMapper $mailAccountMapper,
								IMAPClientFactory $imapClientFactory,
								ITimeFactory $timeFactory,
								ILogger $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->folderMapper = $folderMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	public function sync(Account $account): void {
		if ($account->getMailAccount()->getLastMailboxSync() >= ($this->timeFactory->getTime() - 7200)) {
			$this->logger->debug("account is up to date, skipping mailbox sync");
			return;
		}

		$client = $this->imapClientFactory->getClient($account);

		$folders = $this->folderMapper->getFolders($account, $client);
		$this->folderMapper->getFoldersStatus($folders, $client);
		$this->folderMapper->detectFolderSpecialUse($folders);

		$old = $this->mailboxMapper->findAll($account);
		$indexedOld = array_combine(
			array_map(function (Mailbox $mb) {
				return $mb->getId();
			}, $old),
			$old
		);

		$this->persist($account, $folders, $indexedOld);
	}

	private function persist(Account $account, array $folders, array $existing): void {
		foreach ($folders as $folder) {
			if (isset($existing[$folder->getMailbox()])) {
				$this->updateMailboxFromFolder(
					$folder, $existing[$folder->getMailbox()]
				);
			} else {
				$this->createMailboxFromFolder(
					$account,
					$folder
				);
			}

			unset($existing[$folder->getMailbox()]);
		}

		foreach ($existing as $leftover) {
			$this->mailboxMapper->delete($leftover);
		}

		$account->getMailAccount()->setLastMailboxSync($this->timeFactory->getTime());
		$this->mailAccountMapper->update($account->getMailAccount());
	}

	private function updateMailboxFromFolder(Folder $folder, Mailbox $mailbox): void {
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setSyncToken($folder->getSyncToken());
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setMessages(0); // TODO
		$mailbox->setUnseen(0); // TODO
		$mailbox->setSelectable($folder->isSelectable());
		$this->mailboxMapper->update($mailbox);
	}

	private function createMailboxFromFolder(Account $account, Folder $folder): void {
		$mailbox = new Mailbox();
		$mailbox->setId($folder->getMailbox());
		$mailbox->setAccountId($account->getId());
		$mailbox->setSyncToken($folder->getSyncToken());
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setMessages(0); // TODO
		$mailbox->setUnseen(0); // TODO
		$mailbox->setSelectable($folder->isSelectable());
		$this->mailboxMapper->insert($mailbox);
	}

}
