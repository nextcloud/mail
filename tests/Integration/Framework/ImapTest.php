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

use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use Horde_Mail_Rfc822_Address;
use Horde_Mime_Headers;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\QueryException;
use function in_array;

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
	private function getTestClient(): Horde_Imap_Client_Socket {
		if ($this->client === null) {
			$this->client = new Horde_Imap_Client_Socket([
				'username' => 'user@domain.tld',
				'password' => 'mypassword',
				'hostspec' => '127.0.0.1',
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

	public function disconnectImapAccount(): void {
		if ($this->client === null) {
			return;
		}

		$this->client->logout();
	}

	/**
	 * @return array<string>
	 */
	public function getMailboxes(Horde_Imap_Client_Socket $client = null) {
		if ($client === null) {
			$client = $this->getTestClient();
		}

		return $this->listMailboxes($client);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @return array<string>
	 */
	private function listMailboxes(Horde_Imap_Client_Socket $client) {
		return array_map(function ($mailbox) {
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
	 * @param MailAccount|null $account
	 *
	 * @return int id of the new message
	 */
	public function saveMessage(string $mailbox, SimpleMessage $message, MailAccount $account = null) {
		$headers = [
			'From' => new Horde_Mail_Rfc822_Address($message->getFrom()),
			'To' => new Horde_Mail_Rfc822_Address($message->getTo()),
		];
		if ($message->getCc() !== null) {
			$headers['Cc'] = new Horde_Mail_Rfc822_Address($message->getCc());
		}
		if ($message->getBcc() !== null) {
			$headers['Bcc'] = new Horde_Mail_Rfc822_Address($message->getBcc());
		}
		if ($message->getSubject() !== null) {
			$headers['Subject'] = $message->getSubject();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$mail->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$body = new Horde_Mime_Part();
		$body->setType('text/plain');
		$body->setContents($message->getBody());
		$mail->setBasePart($body);

		$data = $mail->getRaw(false);
		$client = $this->getClient($account);
		try {
			return $client->append($mailbox, [
				[
					'data' => $data,
				]
			])->ids[0];
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param string $mailbox
	 * @param string $mimeText
	 * @param MailAccount|null $account
	 * @return int Uid of the new message
	 */
	public function saveMimeMessage(string $mailbox, string $mimeText, ?MailAccount $account = null): int {
		$headers = Horde_Mime_Headers::parseHeaders($mimeText);
		$mimePart = Horde_Mime_Part::parseMessage($mimeText);

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$mail->setBasePart($mimePart);

		$data = $mail->getRaw(false);
		$client = $this->getClient($account);
		try {
			return $client->append($mailbox, [
				[
					'data' => $mimeText,
				]
			])->ids[0];
		} finally {
			$client->logout();
		}
	}

	public function flagMessage($mailbox, $id, MailAccount $account = null) {
		$client = $this->getClient($account);
		try {
			$client->store($mailbox, [
				'ids' => new Horde_Imap_Client_Ids([$id]),
				'add' => [
					Horde_Imap_Client::FLAG_FLAGGED,
				],
			]);
		} finally {
			$client->logout();
		}
	}

	public function deleteMessage($mailbox, $id, MailAccount $account = null) {
		$client = $this->getClient($account);
		$ids = new Horde_Imap_Client_Ids([$id]);
		try {
			$client->expunge($mailbox, [
				'ids' => $ids,
				'delete' => true,
			]);
		} finally {
			$client->logout();
		}
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
	 * Assert that a mailbox has a certain number of messages in it
	 *
	 * @param int $number
	 * @param string $mailbox
	 */
	public function assertMessageCount(int $number, string $mailbox) {
		$client = $this->getTestClient();

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$this->assertSame($number, $client->fetch($mailbox, $query)->count(), "wrong number of messages in mailbox <$mailbox>");
	}

	/**
	 * Assert that the test account has a certain mailbox
	 *
	 * @param string $mailbox
	 */
	public function assertMailboxExists($mailbox) {
		$mailboxes = $this->getMailboxes();
		$this->assertArrayHasKey($mailbox, $mailboxes);
	}

	public function assertMessageContent($mailbox, $uid, $content) {
		$client = $this->getTestClient();

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->bodyText();
		$result = $client->fetch($mailbox, $query, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);
		$messages = iterator_to_array($result);
		$this->assertCount(1, $messages);
		/* @var $message Horde_Imap_Client_Data_Fetch */
		$message = reset($messages);
		$actualContent = $message->getBodyText();

		$this->assertSame($content, $actualContent, 'message content does not match');
	}

	/**
	 * @param MailAccount|null $account
	 *
	 * @return Horde_Imap_Client_Socket
	 * @throws QueryException
	 */
	protected function getClient(?MailAccount $account): Horde_Imap_Client_Socket {
		if ($account !== null) {
			/** @var IMAPClientFactory $clientFactory */
			$clientFactory = \OC::$server->query(IMAPClientFactory::class);
			$client = $clientFactory->getClient(new Account($account));
		} else {
			$client = $this->getTestClient();
		}
		return $client;
	}
}
