<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Sync;

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\Server;
use Psr\Log\LoggerInterface;

final class ImapToDbSynchronizerTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	private ImapToDbSynchronizer $synchronizer;
	private MailAccount $account;

	protected function setUp(): void {
		parent::setUp();

		$this->synchronizer = Server::get(ImapToDbSynchronizer::class);
		$this->account = $this->createTestAccount();
	}

	public function testRepairSync(): void {
		// Create some test messages
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 1')
			->finish();
		$uid1 = $this->saveMessage($mailbox, $message, $this->account);
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 2')
			->finish();
		$uid2 = $this->saveMessage($mailbox, $message, $this->account);
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 3')
			->finish();
		$uid3 = $this->saveMessage($mailbox, $message, $this->account);

		// Retrieve mailbox object
		$mailManager = Server::get(MailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}

		// Do an initial sync to pull in all created messages
		$syncService = Server::get(SyncService::class);
		$syncService->syncMailbox(
			new Account($this->account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			false,
			null,
			[],
		);

		// Assert that there are 3 messages and nothing changes when deleting a message externally
		$dbMessageMapper = Server::get(DbMessageMapper::class);
		self::assertCount(3, $dbMessageMapper->findAllUids($inbox));
		$this->deleteMessagesExternally($mailbox, [$uid3]);
		self::assertCount(3, $dbMessageMapper->findAllUids($inbox));

		// Do a repair sync to get rid of the vanished message that is still in the cache
		$this->synchronizer->repairSync(
			new Account($this->account),
			$inbox,
			Server::get(LoggerInterface::class),
		);

		// Assert that the cached state has been reconciled with IMAP
		self::assertEqualsCanonicalizing([$uid1, $uid2], $dbMessageMapper->findAllUids($inbox));
	}

	public function testRepairSyncNoopIfNoneVanished(): void {
		// Create some test messages
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 1')
			->finish();
		$uid1 = $this->saveMessage($mailbox, $message, $this->account);
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 2')
			->finish();
		$uid2 = $this->saveMessage($mailbox, $message, $this->account);
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 3')
			->finish();
		$uid3 = $this->saveMessage($mailbox, $message, $this->account);

		// Retrieve mailbox object
		$mailManager = Server::get(MailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}

		// Do an initial sync to pull in all created messages
		$syncService = Server::get(SyncService::class);
		$syncService->syncMailbox(
			new Account($this->account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			false,
			null,
			[],
		);

		// Assert that there are 3 messages and nothing changes when deleting a message externally
		$dbMessageMapper = Server::get(DbMessageMapper::class);
		self::assertCount(3, $dbMessageMapper->findAllUids($inbox));

		// Do a repair sync to get rid of the vanished message that is still in the cache
		$this->synchronizer->repairSync(
			new Account($this->account),
			$inbox,
			Server::get(LoggerInterface::class),
		);

		// Assert that the cached state has been reconciled with IMAP
		self::assertEqualsCanonicalizing([$uid1, $uid2, $uid3], $dbMessageMapper->findAllUids($inbox));
	}

	public function testPartialSyncStoresNewMessagesAcrossChunks(): void {
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->subject('Message 1')
			->finish();
		$uid = $this->saveMessage('INBOX', $message, $this->account);
		$account = new Account($this->account);
		Server::get(MailManager::class)->getMailboxes($account);
		$mailboxMapper = Server::get(MailboxMapper::class);
		$inbox = $mailboxMapper->find($account, 'INBOX');
		Server::get(SyncService::class)->syncMailbox(
			$account,
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			false,
			null,
			[],
		);
		$tokenAfterInitialSync = $mailboxMapper->findById($inbox->getId())->getSyncNewToken();
		$mimeTexts = [];
		for ($i = 1; $i <= ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE + 1; $i++) {
			$mimeTexts[] = "From: ralph@domain.tld\r\nTo: user@domain.tld\r\nSubject: Bulk message $i\r\nMessage-ID: <bulk-$i@domain.tld>\r\n\r\nBody $i\r\n";
		}
		$newUids = $this->saveMimeMessages('INBOX', $mimeTexts, $this->account);

		$this->synchronizer->syncAccount($account, Server::get(LoggerInterface::class));

		self::assertEqualsCanonicalizing([$uid, ...$newUids], Server::get(DbMessageMapper::class)->findAllUids($inbox));
		self::assertNotSame($tokenAfterInitialSync, $mailboxMapper->findById($inbox->getId())->getSyncNewToken());
	}
}
