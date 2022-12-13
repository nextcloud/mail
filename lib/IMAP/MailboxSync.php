<?php

declare(strict_types=1);

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

use Horde_Imap_Client;
use Horde_Imap_Client_Data_Namespace;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Namespace_List;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Exception\ServiceException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function in_array;
use function json_encode;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Folder;
use OCP\AppFramework\Utility\ITimeFactory;
use function sprintf;

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

	/** @var IEventDispatcher */
	private $dispatcher;

	public function __construct(MailboxMapper $mailboxMapper,
								FolderMapper $folderMapper,
								MailAccountMapper $mailAccountMapper,
								IMAPClientFactory $imapClientFactory,
								ITimeFactory $timeFactory,
								IEventDispatcher $dispatcher) {
		$this->mailboxMapper = $mailboxMapper;
		$this->folderMapper = $folderMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->timeFactory = $timeFactory;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @throws ServiceException
	 */
	public function sync(Account $account,
						 LoggerInterface $logger,
						 bool $force = false): void {
		if (!$force && $account->getMailAccount()->getLastMailboxSync() >= ($this->timeFactory->getTime() - 7200)) {
			$logger->debug("account is up to date, skipping mailbox sync");
			return;
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			try {
				$namespaces = $client->getNamespaces([], [
					'ob_return' => true,
				]);
				$account->getMailAccount()->setPersonalNamespace(
					$this->getPersonalNamespace($namespaces)
				);
			} catch (Horde_Imap_Client_Exception $e) {
				$logger->debug('Getting namespaces for account ' . $account->getId() . ' failed: ' . $e->getMessage());
			}

			try {
				$folders = $this->folderMapper->getFolders($account, $client);
				$this->folderMapper->getFoldersStatus($folders, $client);
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException(
					sprintf("IMAP error synchronizing account %d: %s", $account->getId(), $e->getMessage()),
					$e->getCode(),
					$e
				);
			}
			$this->folderMapper->detectFolderSpecialUse($folders);

			$old = $this->mailboxMapper->findAll($account);
			$indexedOld = array_combine(
				array_map(function (Mailbox $mb) {
					return $mb->getName();
				}, $old),
				$old
			);

			$this->persist($account, $folders, $indexedOld);

			$this->dispatcher->dispatchTyped(
				new MailboxesSynchronizedEvent($account)
			);
		} finally {
			$client->logout();
		}
	}

	/**
	 * Sync unread and total message statistics.
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function syncStats(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$stats = $this->folderMapper->getFoldersStatusAsObject($client, $mailbox->getName());
		} catch (Horde_Imap_Client_Exception $e) {
			$id = $mailbox->getId();
			throw new ServiceException(
				"Could not fetch stats of mailbox $id. IMAP error: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		} finally {
			$client->logout();
		}

		$mailbox->setMessages($stats->getTotal());
		$mailbox->setUnseen($stats->getUnread());
		$this->mailboxMapper->update($mailbox);
	}

	/**
	 * @param Account $account
	 * @param Folder[] $folders
	 * @param Mailbox[] $existing
	 */
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

	private function getPersonalNamespace(Horde_Imap_Client_Namespace_List $namespaces): ?string {
		foreach ($namespaces as $namespace) {
			/** @var Horde_Imap_Client_Data_Namespace $namespace */
			if ($namespace->type === Horde_Imap_Client::NS_PERSONAL) {
				return $namespace->name;
			}
		}
		return null;
	}

	private function updateMailboxFromFolder(Folder $folder, Mailbox $mailbox): void {
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setMessages($folder->getStatus()['messages'] ?? 0);
		$mailbox->setUnseen($folder->getStatus()['unseen'] ?? 0);
		$mailbox->setSelectable(!in_array('\noselect', $folder->getAttributes()));
		$mailbox->setSpecialUse(json_encode($folder->getSpecialUse()));
		$mailbox->setMyAcls($folder->getMyAcls());
		$this->mailboxMapper->update($mailbox);
	}

	private function createMailboxFromFolder(Account $account, Folder $folder): void {
		$mailbox = new Mailbox();
		$mailbox->setName($folder->getMailbox());
		$mailbox->setAccountId($account->getId());
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$mailbox->setMessages($folder->getStatus()['messages'] ?? 0);
		$mailbox->setUnseen($folder->getStatus()['unseen'] ?? 0);
		$mailbox->setSelectable(!in_array('\noselect', $folder->getAttributes()));
		$mailbox->setSpecialUse(json_encode($folder->getSpecialUse()));
		$mailbox->setMyAcls($folder->getMyAcls());
		$this->mailboxMapper->insert($mailbox);
	}
}
