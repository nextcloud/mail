<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\ThreadMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;

class ThreadMapperTest extends TestCase {
	use DatabaseTransaction;

	/** A user that has no mail accounts on a development instance */
	private const USER_ID = 'thread-mapper-test-user';
	private const THREAD_ROOT_ID = '<root@example.com>';

	private IDBConnection $db;
	private ThreadMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = new ThreadMapper($this->db);
	}

	private function insertAccount(): MailAccount {
		$account = new MailAccount();
		$account->setUserId(self::USER_ID);
		$account->setEmail(self::USER_ID . '@example.com');
		$account->setInboundHost('imap.example.com');
		$account->setInboundPort(993);
		$account->setInboundSslMode('ssl');
		$account->setInboundUser(self::USER_ID);
		return Server::get(MailAccountMapper::class)->insert($account);
	}

	private function insertMailbox(MailAccount $account, string $name): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setName($name);
		$mailbox->setNameHash(md5($name));
		$mailbox->setAccountId($account->getId());
		$mailbox->setAttributes('[]');
		$mailbox->setDelimiter('.');
		$mailbox->setMessages(0);
		$mailbox->setUnseen(0);
		$mailbox->setSelectable(true);
		$mailbox->setSpecialUse('[]');
		return Server::get(MailboxMapper::class)->insert($mailbox);
	}

	private function insertMessage(Mailbox $mailbox, int $uid, string $messageId, string $threadRootId, int $sentAt): int {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_messages')
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter($messageId),
				'mailbox_id' => $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter($sentAt, IQueryBuilder::PARAM_INT),
				'thread_root_id' => $qb->createNamedParameter($threadRootId),
			])
			->executeStatement();
		return $qb->getLastInsertId();
	}

	public function testFindMessageUidsAndMailboxNamesByAccountAndThreadRootSkipsTheTrash(): void {
		$account = $this->insertAccount();
		$inbox = $this->insertMailbox($account, 'INBOX');
		$trash = $this->insertMailbox($account, 'Trash');
		$account->setTrashMailboxId($trash->getId());
		$otherAccountInbox = $this->insertMailbox($this->insertAccount(), 'INBOX');
		$this->insertMessage($inbox, 1, '<1@example.com>', self::THREAD_ROOT_ID, 100);
		$this->insertMessage($trash, 2, '<2@example.com>', self::THREAD_ROOT_ID, 200);
		$this->insertMessage($inbox, 3, '<3@example.com>', '<other@example.com>', 300);
		$this->insertMessage($otherAccountInbox, 4, '<4@example.com>', self::THREAD_ROOT_ID, 400);

		$result = $this->mapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot($account, self::THREAD_ROOT_ID, false);

		self::assertSame([['messageUid' => 1, 'mailboxName' => 'INBOX']], $result);
	}

	public function testFindMessageUidsAndMailboxNamesByAccountAndThreadRootInTheTrash(): void {
		$account = $this->insertAccount();
		$inbox = $this->insertMailbox($account, 'INBOX');
		$trash = $this->insertMailbox($account, 'Trash');
		$account->setTrashMailboxId($trash->getId());
		$this->insertMessage($inbox, 1, '<1@example.com>', self::THREAD_ROOT_ID, 100);
		$this->insertMessage($trash, 2, '<2@example.com>', self::THREAD_ROOT_ID, 200);

		$result = $this->mapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot($account, self::THREAD_ROOT_ID, true);

		self::assertSame([['messageUid' => 2, 'mailboxName' => 'Trash']], $result);
	}

	public function testFindMessageUidsAndMailboxNamesByAccountAndThreadRootWithoutTrashMailbox(): void {
		$account = $this->insertAccount();
		$inbox = $this->insertMailbox($account, 'INBOX');
		$archive = $this->insertMailbox($account, 'Archive');
		$this->insertMessage($inbox, 1, '<1@example.com>', self::THREAD_ROOT_ID, 100);
		$this->insertMessage($archive, 2, '<2@example.com>', self::THREAD_ROOT_ID, 200);

		$result = $this->mapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot($account, self::THREAD_ROOT_ID, false);

		self::assertEqualsCanonicalizing([
			['messageUid' => 1, 'mailboxName' => 'INBOX'],
			['messageUid' => 2, 'mailboxName' => 'Archive'],
		], $result);
	}

	public function testFindNewerMessageIdsInThread(): void {
		$account = $this->insertAccount();
		$inbox = $this->insertMailbox($account, 'INBOX');
		$sent = $this->insertMailbox($account, 'Sent');
		$otherAccountInbox = $this->insertMailbox($this->insertAccount(), 'INBOX');
		$this->insertMessage($inbox, 1, '<1@example.com>', self::THREAD_ROOT_ID, 100);
		$newer = $this->insertMessage($sent, 2, '<2@example.com>', self::THREAD_ROOT_ID, 200);
		$this->insertMessage($inbox, 3, '<3@example.com>', self::THREAD_ROOT_ID, 50);
		$this->insertMessage($otherAccountInbox, 4, '<4@example.com>', self::THREAD_ROOT_ID, 300);
		$this->insertMessage($inbox, 5, '<5@example.com>', '<other@example.com>', 300);
		$message = new Message();
		$message->setMessageId('<1@example.com>');
		$message->setThreadRootId(self::THREAD_ROOT_ID);
		$message->setSentAt(100);

		$result = $this->mapper->findNewerMessageIdsInThread($account->getId(), $message);

		self::assertSame([['id' => $newer]], $result);
	}
}
