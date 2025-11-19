<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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

	public function __construct(
		private readonly \Psr\Log\LoggerInterface $logger
	) {
	}
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
		$toPersist = array_filter($mailboxes, function (array $mailbox): bool {
			$attributes = array_flip(array_map(strtolower(...), $mailbox['attributes']));
			if (isset($attributes['\\nonexistent'])) {
				// Ignore mailbox that does not exist, similar to \Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS mode
				return false;
			}
			// This is a special folder that must not be shown
			return !in_array($mailbox['mailbox']->utf8, self::DOVECOT_SIEVE_FOLDERS, true);
		});
		return array_map(static fn (array $mailbox): \OCA\Mail\Folder => new Folder(
			$mailbox['mailbox'],
			$mailbox['attributes'],
			$mailbox['delimiter'],
			null,
		), $toPersist);
	}

	public function createFolder(Horde_Imap_Client_Socket $client,
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
			throw new ServiceException('Created mailbox does not exist');
		}

		return new Folder(
			$mb['mailbox'],
			$mb['attributes'],
			$mb['delimiter'],
			$mb['status'],
		);
	}

	/**
	 * @param Folder[] $folders
	 *
	 * @throws Horde_Imap_Client_Exception
	 *
	 */
	public function fetchFolderAcls(array $folders,
		Horde_Imap_Client_Socket $client): void {
		$hasAcls = $client->capability->query('ACL');

		foreach ($folders as $folder) {
			$acls = null;
			if ($hasAcls && !in_array('\\noselect', array_map(strtolower(...), $folder->getAttributes()), true)) {
				$acls = (string)$client->getMyACLRights($folder->getMailbox());
			}

			$folder->setMyAcls($acls);
		}
	}

	/**
	 * @param string $mailbox
	 *
	 * @throws Horde_Imap_Client_Exception
	 * @return MailboxStats[]
	 */
	public function getFoldersStatusAsObject(Horde_Imap_Client_Socket $client,
		array $mailboxes): array {
		$statuses = [];
		foreach ($mailboxes as $mailbox) {
			$status = $client->status($mailbox);
			try {
				if (!isset($status['messages'], $status['unseen'])) {
					throw new ServiceException('Could not fetch stats of mailbox: ' . $mailbox);
				}
				$statuses[$mailbox] = new MailboxStats(
					$status['messages'],
					$status['unseen'],
				);
			} catch (ServiceException $e) {
				$this->logger->warning($e->getMessage(), [
					'exception' => $e,
					'mailboxes' => $mailboxes,
					'status' => $status,
				]);
			}
		}
		return $statuses;
	}

	/**
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
	 *
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

		$attributes = array_map(strtolower(...), $folder->getAttributes());

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
	 *
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
	 * @throws ServiceException
	 */
	public function delete(Horde_Imap_Client_Socket $client, string $folderId): void {
		try {
			$client->deleteMailbox($folderId);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not delete mailbox: ' . $e->getMessage(), 0, $e);
		}
	}
}
