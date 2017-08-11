<?php

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

namespace OCA\Mail\Tests\Integration\Framework;

use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use Horde_Mail_Rfc822_Address;
use Horde_Mime_Mail;
use Horde_Mime_Part;

trait ImapTest {

	/**  @var Horde_Imap_Client_Socket */
	private $client;

	/** @var array<string> */
	private $defaultMailboxes = [
		'INBOX',
		'Junk',
		'Sent',
		'Trash',
	];

	/**
	 * @return Horde_Imap_Client_Socket
	 */
	private function getTestClient() {
		if (is_null($this->client)) {
			$this->client = new Horde_Imap_Client_Socket([
				'username' => 'user@domain.tld',
				'password' => 'mypassword',
				'hostspec' => 'localhost',
				'port' => 993,
				'secure' => 'ssl',
			]);
		}

		return $this->client;
	}

	/**
	 * Reset the testing account to empty default mailboxes and delete any
	 * other mailboxes that have been created
	 */
	public function resetImapAccount() {
		$client = $this->getTestClient();
		$mailboxes = $this->listMailboxes($client);

		foreach ($mailboxes as $mailbox) {
			if (in_array($mailbox, $this->defaultMailboxes)) {
				$this->emptyMailbox($client, $mailbox);
			} else {
				$this->deleteMailbox($client, $mailbox);
			}
		}
	}

	/**
	 * @return array<string>
	 */
	public function getMailboxes() {
		$client = $this->getTestClient();

		return $this->listMailboxes($client);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @return array<string>
	 */
	private function listMailboxes(Horde_Imap_Client_Socket $client) {
		return array_map(function($mailbox) {
			return $mailbox['mailbox'];
		}, $client->listMailboxes('*'));
	}

	/**
	 * @param string $mailbox
	 */
	public function createImapMailbox($mailbox) {
		$client = $this->getTestClient();

		$client->createMailbox($mailbox);
	}

	/**
	 * @return MessageBuilder
	 */
	public function getMessageBuilder() {
		return MessageBuilder::create();
	}

	/**
	 * @param string $mailbox
	 * @param SimpleMessage $message
	 */
	public function saveMessage($mailbox, SimpleMessage $message) {
		$client = $this->getTestClient();

		$headers = [
			'From' => new Horde_Mail_Rfc822_Address($message->getFrom()),
			'To' => new Horde_Mail_Rfc822_Address($message->getTo()),
			'Cc' => new Horde_Mail_Rfc822_Address($message->getCc()),
			'Bcc' => new Horde_Mail_Rfc822_Address($message->getBcc()),
			'Subject' => $message->getSubject(),
		];

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$body = new Horde_Mime_Part();
		$body->setType('text/plain');
		$body->setContents($message->getBody());
		$mail->setBasePart($body);

		$raw = $mail->getRaw();
		$data = stream_get_contents($raw);

		$client->append($mailbox, [
			[
				'data' => $data,
			]
		]);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 */
	private function emptyMailbox(Horde_Imap_Client_Socket $client, $mailbox) {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$ids = new Horde_Imap_Client_Ids($client->fetch($mailbox, $query)->ids());

		$client->expunge($mailbox, [
			'ids' => $ids,
			'delete' => true,
		]);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 */
	private function deleteMailbox(Horde_Imap_Client_Socket $client, $mailbox) {
		$client->deleteMailbox($mailbox);
	}

	/**
	 * @param int $number
	 * @param string $mailbox
	 */
	public function assertMessageCount($number, $mailbox) {
		$client = $this->getTestClient();

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$this->assertSame($number, $client->fetch($mailbox, $query)->count());
	}

}
