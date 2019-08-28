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

use function array_filter;
use function array_map;
use Horde_Imap_Client_Mailbox;
use function reset;
use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\SearchFolder;

class FolderMapper {

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
		$mailboxes = $client->listMailboxes($pattern, Horde_Imap_Client::MBOX_ALL, [
			'delimiter' => true,
			'attributes' => true,
			'special_use' => true,
		]);

		$folders = [];
		foreach ($mailboxes as $mailbox) {
			/**
			 * This is a temporary workaround for when the sieve folder is a subfolder of
			 * INBOX. Once "#386 Subfolders and Dovecot" has been resolved, we can go back
			 * to just comparing to 'dovecot.sieve'.
			 */
			$dovecotSieveFolders = [
				'dovecot.sieve',
				'INBOX.dovecot.sieve'
			];
			if (in_array($mailbox['mailbox']->utf8, $dovecotSieveFolders, true)) {
				// This is a special folder that must not be shown
				continue;
			}

			$folder = new Folder($account, $mailbox['mailbox'], $mailbox['attributes'], $mailbox['delimiter']);

			if ($folder->isSearchable()) {
				$folder->setSyncToken($client->getSyncToken($folder->getMailbox()));
			}

			$folders[] = $folder;
			if ($mailbox['mailbox']->utf8 === 'INBOX') {
				$searchFolder = new SearchFolder($account, $mailbox['mailbox'], $mailbox['attributes'], $mailbox['delimiter']);
				if ($folder->isSearchable()) {
					$searchFolder->setSyncToken($client->getSyncToken($folder->getMailbox()));
				}
				$folders[] = $searchFolder;
			}
		}
		return $folders;
	}

	public function createFolder(Horde_Imap_Client_Socket $client,
								 Account $account,
								 string $name): Folder {
		$client->createMailbox($name);

		$list = $client->listMailboxes($name, Horde_Imap_Client::MBOX_ALL, [
			'delimiter' => true,
			'attributes' => true,
			'special_use' => true,
		]);
		$mb = reset($list);

		if ($mb === null) {
			throw new ServiceException("Created mailbox does not exist");
		}

		return new Folder($account, $mb['mailbox'], $mb['attributes'], $mb['delimiter']);
	}

	/**
	 * @param Folder[] $folders
	 * @param Horde_Imap_Client_Socket $client
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getFoldersStatus(array $folders,
									 Horde_Imap_Client_Socket $client) {
		$mailboxes = array_map(function (Folder $folder) {
			return $folder->getMailbox();
		}, array_filter($folders, function (Folder $folder) {
			return $folder->isSearchable();
		}));

		$status = $client->status($mailboxes);

		foreach ($folders as $folder) {
			if (isset($status[$folder->getMailbox()])) {
				$folder->setStatus($status[$folder->getMailbox()]);
			}
		}
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getFoldersStatusAsObject(Horde_Imap_Client_Socket $client,
											 string $mailbox) {
		$status = $client->status($mailbox);

		return new FolderStats(
			$status['messages'],
			$status['unseen']
		);
	}

	/**
	 * @param Folder[] $folders
	 */
	public function detectFolderSpecialUse(array $folders) {
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
	 */
	protected function detectSpecialUse(Folder $folder) {
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

		$attributes = array_map(function ($n) {
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
	 */
	protected function guessSpecialUse(Folder $folder) {
		$specialFoldersDict = [
			'inbox' => ['inbox'],
			'sent' => ['sent', 'sent items', 'sent messages', 'sent-mail', 'sentmail'],
			'drafts' => ['draft', 'drafts'],
			'archive' => ['archive', 'archives'],
			'trash' => ['deleted messages', 'trash'],
			'junk' => ['junk', 'spam', 'bulk mail'],
		];

		$lowercaseExplode = explode($folder->getDelimiter(), $folder->getMailbox(), 2);
		$lowercaseId = strtolower(array_pop($lowercaseExplode));
		foreach ($specialFoldersDict as $specialRole => $specialNames) {
			if (in_array($lowercaseId, $specialNames)) {
				$folder->addSpecialUse($specialRole);
			}
		}
	}

}
