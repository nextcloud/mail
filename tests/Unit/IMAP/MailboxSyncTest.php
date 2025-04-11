<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Data_Namespace;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Namespace_List;
use Horde_Imap_Client_Socket;
use OC\AppFramework\Utility\TimeFactory;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class MailboxSyncTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var FolderMapper|MockObject */
	private $folderMapper;

	/** @var MailAccountMapper|MockObject */
	private $mailAccountMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var TimeFactory|MockObject */
	private $timeFactory;

	/** @var MailboxSync */
	private $sync;

	/** @var IEventDispatcher|MockObject */
	private $dispatcher;
	/** @var IDBConnection|(IDBConnection&MockObject)|MockObject */
	private IDBConnection|MockObject $dbConnection;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->dbConnection = $this->createMock(IDBConnection::class);

		$this->sync = new MailboxSync(
			$this->mailboxMapper,
			$this->folderMapper,
			$this->mailAccountMapper,
			$this->imapClientFactory,
			$this->timeFactory,
			$this->dispatcher,
			$this->dbConnection,
		);
	}

	public function testSyncSkipped() {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setLastMailboxSync(100000 - 2000);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->timeFactory->method('getTime')->willReturn(100000);
		$this->imapClientFactory->expects($this->never())
			->method('getClient');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->sync->sync($account, new NullLogger());
	}

	public function testSync(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setLastMailboxSync(0);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->timeFactory->method('getTime')->willReturn(100000);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('getNamespaces')
			->willThrowException(new Horde_Imap_Client_Exception('', 0));
		$folders = [
			$this->createMock(Folder::class),
			$this->createMock(Folder::class),
			$this->createMock(Folder::class),
		];
		$status = [
			'unseen' => 10,
			'messages' => 42,
		];
		$folders[0]->method('getStatus')->willReturn($status);
		$folders[0]->method('getMailbox')->willReturn('mb1');
		$folders[1]->method('getStatus')->willReturn($status);
		$folders[1]->method('getMailbox')->willReturn('mb2');
		$folders[2]->method('getStatus')->willReturn($status);
		$folders[2]->method('getMailbox')->willReturn('mb3');
		$this->folderMapper->expects($this->once())
			->method('getFolders')
			->with($account, $client)
			->willReturn($folders);
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatusAsObject')
			->with($client, self::equalToCanonicalizing(['mb1', 'mb2', 'mb3',]))
			->willReturn([
				'mb1' => new MailboxStats(1, 2),
				'mb2' => new MailboxStats(1, 2),
				/* no status for mb3 */
			]);
		$this->folderMapper->expects($this->once())
			->method('detectFolderSpecialUse')
			->with($folders);
		$this->mailboxMapper->expects(self::exactly(3))
			->method('insert')
			->willReturnArgument(0);
		$this->mailboxMapper->expects(self::exactly(3))
			->method('update')
			->willReturnArgument(0);
		$this->dispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($this->equalTo(new MailboxesSynchronizedEvent($account)));

		$this->sync->sync($account, new NullLogger());
	}

	public function testSyncShared(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setLastMailboxSync(0);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->timeFactory->method('getTime')->willReturn(100000);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$personal = new Horde_Imap_Client_Data_Namespace();
		$personal->name = '';
		$personal->type = Horde_Imap_Client_Data_Namespace::NS_PERSONAL;
		$shared = new Horde_Imap_Client_Data_Namespace();
		$shared->name = 'Shared/';
		$shared->type = Horde_Imap_Client_Data_Namespace::NS_OTHER;
		$client->expects($this->once())
			->method('getNamespaces')
			->willReturn(new Horde_Imap_Client_Namespace_List([
				$personal,
				$shared,
			]));
		$folders = [
			$this->createMock(Folder::class),
			$this->createMock(Folder::class),
		];
		$status = [
			'unseen' => 10,
			'messages' => 42,
		];
		$folders[0]->method('getStatus')->willReturn($status);
		$folders[1]->method('getStatus')->willReturn($status);
		$folders[0]->method('getMailbox')->willReturn('INBOX');
		$folders[1]->method('getMailbox')->willReturn('Shared/Foo');
		$this->folderMapper->expects($this->once())
			->method('getFolders')
			->with($account, $client)
			->willReturn($folders);
		$inbox = new Mailbox();
		$inbox->setId(101);
		$inbox->setName('INBOX');
		$sharedMailbox = new Mailbox();
		$sharedMailbox->setId(102);
		$sharedMailbox->setName('Shared/Foo');
		$this->mailboxMapper->expects(self::once())
			->method('findAll')
			->with($account)
			->willReturn([$inbox, $sharedMailbox]);
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatusAsObject')
			->with($client, [$inbox->getName(), $sharedMailbox->getName()])
			->willReturn([
				$inbox->getName() => new MailboxStats(1, 2),
				$sharedMailbox->getName() => new MailboxStats(0, 0),
			]);
		$this->mailboxMapper->expects(self::exactly(4))
			->method('update')
			->willReturnArgument(0);

		$this->sync->sync($account, new NullLogger());
	}

	public function testSyncStats(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$stats = new MailboxStats(42, 10, null);
		$mailbox = new Mailbox();
		$mailbox->setName('mailbox');
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatusAsObject')
			->with($client, [$mailbox->getName()])
			->willReturn(['mailbox' => $stats]);
		$this->mailboxMapper->expects($this->once())
			->method('update')
			->with($mailbox);

		$this->sync->syncStats($client, $mailbox);

		$this->assertEquals(42, $mailbox->getMessages());
		$this->assertEquals(10, $mailbox->getUnseen());
	}

	public function testSyncStatsWithNoStats(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$stats = new MailboxStats(42, 10, null);
		$mailbox = new Mailbox();
		$mailbox->setMessages(10);
		$mailbox->setUnseen(6);
		$mailbox->setName('mailbox');
		$this->folderMapper->expects(self::once())
			->method('getFoldersStatusAsObject')
			->with($client, [$mailbox->getName()])
			->willReturn(['otherMailbox' => $stats]);
		$this->mailboxMapper->expects(self::never())
			->method('update');

		$this->sync->syncStats($client, $mailbox);

		$this->assertEquals(10, $mailbox->getMessages());
		$this->assertEquals(6, $mailbox->getUnseen());
	}
}
