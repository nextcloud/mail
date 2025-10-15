<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Migration\MigrateImportantFromImapAndDb;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MigrateImportantFromImapAndDbTest extends TestCase {
	private Horde_Imap_Client_Socket|MockObject $client;
	private MockObject|MessageMapper $messageMapper;
	private MailboxMapper|MockObject $mailboxMapper;
	private MockObject|LoggerInterface $logger;
	private MigrateImportantFromImapAndDb $migration;
	private IMAPClientFactory|MockObject $clientFactory;

	protected function setUp(): void {
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->migration = new MigrateImportantFromImapAndDb(
			$this->messageMapper,
			$this->mailboxMapper,
			$this->logger
		);
		parent::setUp();
	}

	public function testMigrateImportantOnImap() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$uids = [1,2,3];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapNoUids() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$uids = [];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapExceptionGetFlagged() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$e = new Horde_Imap_Client_Exception('');

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willThrowException($e);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapExceptionOnFlag() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$e = new Horde_Imap_Client_Exception('');
		$uids = [1,2,3,4];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function migrateImportantFromDb() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$uids = [1,2,3];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}

	public function testMigrateImportantFromDbNoUids() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$uids = [];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}

	public function testMigrateImportantFromDbExceptionOnFlag() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setName('INBOX');
		$e = new Horde_Imap_Client_Exception('');
		$uids = [1,2,3];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}
}
