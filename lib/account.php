<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use OCA\Mail\Cache\Cache;
use Horde_Imap_Client;
use OCA\Mail\Db\MailAccount;

class Account {

	/**
	 * @var MailAccount
	 */
	private $account;

	/**
	 *  @var Mailbox[]
	 */
	private $mailboxes;

	/**
	 * @param MailAccount $info
	 */
	function __construct(MailAccount $account) {
		$this->account = $account;
		$this->mailboxes = null;
	}

	public function getId() {
		return $this->account->getId();
	}

	public function getName() {
		return $this->account->getName();
	}

	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	public function getImapConnection() {
		$host = $this->account->getInboundHost();
		$user = $this->account->getInboundUser();
		$password = $this->account->getInboundPassword();
		$port = $this->account->getInboundPort();
		$ssl_mode = $this->account->getInboundSslMode();

		$client = new \Horde_Imap_Client_Socket(
			array(
				'username' => $user,
				'password' => $password,
				'hostspec' => $host,
				'port' => $port,
				'secure' => $ssl_mode,
				'timeout' => 20,
//				'cache' => array(
//					'backend' => new Cache(array(
//							'cacheob' => \OC::$server->getCache()
//						))
//				)
			));
		$client->login();
		return $client;
	}

	/**
	 * Lists mailboxes (folders) for this account.
	 *
	 * Lists mailboxes and also queries the server for their 'special use',
	 * eg. inbox, sent, trash, etc
	 *
	 * @param string $pattern Pattern to match mailboxes against. All by default.
	 * @return Mailbox[]
	 */
	protected function listMailboxes($pattern='*') {
		// open the imap connection
		$conn = $this->getImapConnection();

		// if successful -> get all folders of that account
		$mboxes = $conn->listMailboxes($pattern, Horde_Imap_Client::MBOX_ALL, array(
			'attributes' => true,
			'special_use' => true,
			'sort' => true
		));

		$mailboxes = array();
		foreach ($mboxes as $mailbox) {
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox']->utf7imap, $mailbox['attributes'], $mailbox['delimiter']);
		}
		return $mailboxes;
	}

	/**
	 * @param $folderId
	 * @return \OCA\Mail\Mailbox
	 */
	public function getMailbox($folderId) {
		$conn = $this->getImapConnection();
		return new Mailbox($conn, $folderId, array());
	}

	/**
	 * Get a list of all mailboxes in this account
	 *
	 * @return Mailbox[]
	 */
	protected function getMailboxes() {
		if ($this->mailboxes === null) {
			$this->mailboxes = $this->listMailboxes();
			$this->sortMailboxes();
		}

		return $this->mailboxes;
	}

	/**
	 * @return array
	 */
	public function getListArray() {

		$folders = array();
		foreach ($this->getMailboxes() as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}
		return array(
			'id'             => $this->getId(),
			'email'          => $this->getEMailAddress(),
			'folders'        => array_values($folders),
			'specialFolders' => $this->getSpecialFoldersIds()
		);
	}


	/**
	 * @return \Horde_Mail_Transport_Smtphorde
	 */
	public function createTransport() {
		$host = $this->account->getOutboundHost();
		$params = array(
			'host' => $host,
			'password' => $this->account->getOutboundPassword(),
			'port' => $this->account->getOutboundPort(),
			'username' => $this->account->getOutboundUser(),
			'secure' => $this->account->getOutboundSslMode(),
			'timeout' => 2
		);
		return new \Horde_Mail_Transport_Smtphorde($params);
	}
	
	/**
	 * Lists special use folders for this account.
	 *
	 * The special uses returned are the "best" one for each special role,
	 * picked amongst the ones returned by the server, as well
	 * as the one guessed by our code.
	 * 
	 * @return array In the form array(<special use>=><folder id>, ...)
	 */
	public function getSpecialFoldersIds() {
		$folderRoles = array('inbox', 'sent', 'draft', 'trash', 'archive', 'junk');
		$specialFoldersIds = array();
		
		foreach ($folderRoles as $role) {
			$folder = $this->getSpecialFolder($role, true);
			$specialFoldersIds[$role] = empty($folder) ? null : base64_encode($folder->getFolderId());
		}
		return $specialFoldersIds;
	}

	/**
	 * Get the "sent mail" mailbox
	 *
	 * @return Mailbox The best candidate for the "sent mail" inbox
	 */
	public function getSentFolder() {
		return $this->getSpecialFolder('sent', true);
	}
	
	/*
	 * Get mailbox(es) that have the given special use role
	 *
	 * With this method we can get a list of all mailboxes tht have been
	 * determined to have a specific special use role. It can also return
	 * the best candidate for this role, for situations where we want
	 * one single folder. Right now the best candidate is the one with
	 * the most messages in it.
	 *
	 * @param string $role Special role of the folder we want to get ('sent', 'inbox', etc.)
	 * @param bool $guessBest If set to true, return only the folder with the most messages in it
	 *
	 * @return Mailbox[] if $guessBest is false, or Mailbox if $guessBest is true. Empty array() if no match.
	 */ 
	protected function getSpecialFolder($role, $guessBest=true) {
		
		$specialFolders = array();
		foreach ($this->getMailboxes() as $mailbox) {
			if ($role === $mailbox->getSpecialRole()) {
				$specialFolders[] = $mailbox;
			}
		}

		if ($guessBest === true && count($specialFolders) > 0) {
			$maxMessages = 0;
			$maxFolder = reset($specialFolders);
			foreach ($specialFolders as $folder) {
				if ($folder->getTotalMessages() > $maxMessages) {
					$maxMessages = $folder->getTotalMessages();
					$maxFolder = $folder;
				}
			}
			return $maxFolder;
		} else {
			return $specialFolders;
		}
	}

	/**
	 * Sort mailboxes
	 *
	 * Sort the array of mailboxes with 
	 *  - special use folders coming first in this order: all, inbox, draft, sent, archive, junk, trash 
	 *  - 'normal' folders coming after that, sorted alphabetically
	 */
	protected function sortMailboxes() {

		$mailboxes = $this->getMailboxes();
		usort($mailboxes, function($a, $b) {
			$roleA = $a->getSpecialRole();
			$roleB = $b->getSpecialRole();
			if ($roleA === null && $roleB !== null) {
				return 1;
			} elseif ($roleA !== null && $roleB === null){
				return -1;
			} elseif ($roleA !== null && $roleB !== null) {
				if ($roleA === $roleB) {
					return strcasecmp($a->getdisplayName(), $b->getDisplayName());
				} else {
					$specialRolesOrder = array(
						'all'     => 0,
						'inbox'   => 1,
						'draft'   => 2,
						'sent'    => 3,
						'archive' => 4,
						'junk'    => 5,
						'trash'   => 6,
					);
					return $specialRolesOrder[$roleA] - $specialRolesOrder[$roleB];
				}
			} elseif ($roleA === null && $roleB === null) {
				return strcasecmp($a->getDisplayName(), $b->getDisplayName());
			}
		});

		$this->mailboxes = $mailboxes;
	}
}

