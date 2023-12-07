<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use function array_filter;
use function array_map;
use function in_array;
use function reset;

class FolderMapper {
	/**
	 * This is a temporary workaround for when the sieve folder is a subfolder of
	 * INBOX. Once "#386 Subfolders and Dovecot" has been resolved, we can go back
	 * to just comparing to 'dovecot.sieve'.
	 */
	private const DOVECOT_SIEVE_FOLDERS = [
		'dovecot.sieve',
		'INBOX.dovecot.sieve'
	];

	/**
	 * @param Account $account
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $pattern
	 *
	 * @return Folder[]
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getFolders(Account $account, Horde_Imap_Client_Socket $client,
		string $pattern = '*'): array {
		$mailboxes = $client->listMailboxes($pattern, Horde_Imap_Client::MBOX_ALL_SUBSCRIBED, [
			'delimiter' => true,
			'attributes' => true,
			'special_use' => true,
		]);
		$toPersist = array_filter($mailboxes, function (array $mailbox) {
			$attributes = array_flip(array_map('strtolower', $mailbox['attributes']));
			if (isset($attributes['\\nonexistent'])) {
				// Ignore mailbox that does not exist, similar to \Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS mode
				return false;
			}
			// This is a special folder that must not be shown
			return !in_array($mailbox['mailbox']->utf8, self::DOVECOT_SIEVE_FOLDERS, true);
		});
		return array_map(static function (array $mailbox) use ($account) {
			return new Folder(
				$account->getId(),
				$mailbox['mailbox'],
				$mailbox['attributes'],
				$mailbox['delimiter'],
				null,
			);
		}, $toPersist);
	}

	public function createFolder(Horde_Imap_Client_Socket $client,
		Account $account,
		string $name): Folder {
		$client->createMailbox($name);

		$list = $client->listMailboxes($name, Horde_Imap_Client::MBOX_ALL_SUBSCRIBED, [
			'delimiter' => true,
			'attributes' => true,
			'special_use' => true,
			'status' => Horde_Imap_Client::STATUS_ALL,
		]);
		$mb = reset($list);

		if ($mb === null) {
			throw new ServiceException("Created mailbox does not exist");
		}

		return new Folder(
			$account->getId(),
			$mb['mailbox'],
			$mb['attributes'],
			$mb['delimiter'],
			$mb['status'],
		);
	}

	/**
	 * @param Folder[] $folders
	 * @param Horde_Imap_Client_Socket $client
	 *
	 * @throws Horde_Imap_Client_Exception
	 *
	 * @return void
	 */
	public function fetchFolderAcls(array $folders,
		Horde_Imap_Client_Socket $client): void {
		$hasAcls = $client->capability->query('ACL');

		foreach ($folders as $folder) {
			$acls = null;
			if ($hasAcls && !in_array('\\noselect', array_map('strtolower', $folder->getAttributes()), true)) {
				$acls = (string)$client->getMyACLRights($folder->getMailbox());
			}

			$folder->setMyAcls($acls);
		}
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 *
	 * @throws Horde_Imap_Client_Exception
	 *
	 * @return MailboxStats[]
	 */
	public function getFoldersStatusAsObject(Horde_Imap_Client_Socket $client,
		array $mailboxes): array {
		$multiStatus = $client->status($mailboxes);

		$statuses = [];
		foreach ($multiStatus as $mailbox => $status) {
			$statuses[$mailbox] = new MailboxStats(
				$status['messages'],
				$status['unseen'],
			);
		}
		return $statuses;
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $oldName
	 * @param string $newName
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	public function renameFolder(Horde_Imap_Client_Socket $client,
		string $oldName,
		string $newName): void {
		$client->renameMailbox($oldName, $newName);
	}

	/**
	 * @param Folder[] $folders
	 *
	 * @return void
	 */
	public function detectFolderSpecialUse(array $folders): void {
		foreach ($folders as $folder) {
			$this->detectSpecialUse($folder);
		}
	}

	/**
	 * Get the special use of the mailbox
	 *
	 * This method reads the attributes sent by the server
	 *
	 * @param Folder $folder
	 *
	 * @return void
	 */
	protected function detectSpecialUse(Folder $folder): void {
		/*
		 * @todo: support multiple attributes on same folder
		 * "any given server or  message store may support
		 *  any combination of the attributes"
		 *  https://tools.ietf.org/html/rfc6154
		 */
		/* Convert attributes to lowercase, because gmail
		 * returns them as lowercase (eg. \trash and not \Trash)
		 */
		$specialUseAttributes = [
			strtolower(Horde_Imap_Client::SPECIALUSE_ALL),
			strtolower(Horde_Imap_Client::SPECIALUSE_ARCHIVE),
			strtolower(Horde_Imap_Client::SPECIALUSE_DRAFTS),
			strtolower(Horde_Imap_Client::SPECIALUSE_FLAGGED),
			strtolower(Horde_Imap_Client::SPECIALUSE_JUNK),
			strtolower(Horde_Imap_Client::SPECIALUSE_SENT),
			strtolower(Horde_Imap_Client::SPECIALUSE_TRASH)
		];

		$attributes = array_map(static function ($n) {
			return strtolower($n);
		}, $folder->getAttributes());

		foreach ($specialUseAttributes as $attr) {
			if (in_array($attr, $attributes)) {
				$folder->addSpecialUse(ltrim($attr, '\\'));
			}
		}

		if (empty($folder->getSpecialUse())) {
			$this->guessSpecialUse($folder);
		}
	}

	/**
	 * Assign a special use based on the name
	 *
	 * @param Folder $folder
	 *
	 * @return void
	 */
	protected function guessSpecialUse(Folder $folder): void {
		$specialFoldersDict = [
			'inbox' => ['inbox'],
			'sent' => ['sent', 'sent items', 'sent messages', 'sent-mail', 'sentmail'],
			'drafts' => ['draft', 'drafts'],
			'archive' => ['archive', 'archives'],
			'trash' => ['deleted messages', 'trash'],
			'junk' => ['junk', 'spam', 'bulk mail'],
		];

		if ($folder->getDelimiter() === null) {
			$lowercaseExplode = [$folder->getMailbox()];
		} else {
			$lowercaseExplode = explode($folder->getDelimiter(), $folder->getMailbox(), 2);
		}
		$lowercaseId = strtolower(array_pop($lowercaseExplode));

		foreach ($specialFoldersDict as $specialRole => $specialNames) {
			if (in_array($lowercaseId, $specialNames)) {
				$folder->addSpecialUse($specialRole);
			}
		}
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $folderId
	 * @throws ServiceException
	 */
	public function delete(Horde_Imap_Client_Socket $client, string $folderId): void {
		try {
			$client->deleteMailbox($folderId);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not delete mailbox: '.$e->getMessage(), 0, $e);
		}
	}
}
