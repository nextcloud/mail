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
use OCA\Mail\Db\MailAccount;

class Account {

	/**
	 * @var MailAccount
	 */
	private $account;

	/**
	 * @param MailAccount $info
	 */
	function __construct(MailAccount $account) {
		$this->account = $account;
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
				'timeout' => 2,
				'cache' => array(
					'backend' => new Cache(array(
							'cacheob' => \OC::$server->getCache()
						))
				)
			));
		$client->login();
		return $client;
	}

	/**
	 * @param string $pattern
	 * @return Mailbox[]
	 */
	public function listMailboxes($pattern) {
		// open the imap connection
		$conn = $this->getImapConnection();

		// if successful -> get all folders of that account
		$mboxes = $conn->listMailboxes($pattern);
		$mailboxes = array();
		foreach ($mboxes as $mailbox) {
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox']->utf7imap);
		}
		return $mailboxes;
	}

	/**
	 * @param $folder_id
	 * @return \OCA\Mail\Mailbox
	 */
	public function getMailbox($folder_id) {
		$conn = $this->getImapConnection();
		return new Mailbox($conn, $folder_id);
	}

	/**
	 * @return array
	 */
	public function getListArray() {
		// if successful -> get all folders of that account
		$mboxes = $this->listMailboxes('*');

		$folders = array();
		foreach ($mboxes as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}

		$inbox = null;
		foreach ($folders as $key=>$value) {
			if ($value['id'] === base64_encode('INBOX')) {
				  $inbox = $key;
			}
		}

		if ($inbox) {
			self::move_to_top($folders, $inbox);
		}

		return array('id' => $this->getId(), 'email' => $this->getEMailAddress(), 'folders' => array_values( $folders));
	}

	private static function move_to_top(&$array, $key) {
		$temp = array($key => $array[$key]);
		unset($array[$key]);
		$array = $temp + $array;
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

	public function getSentFolder() {
		//
		// TODO: read settings/server special folders how the sent folder is named
		//
		$conn = $this->getImapConnection();
		return new Mailbox($conn, 'Sent');
	}
}
