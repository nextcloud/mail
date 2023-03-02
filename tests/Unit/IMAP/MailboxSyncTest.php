<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
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

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->sync = new MailboxSync(
			$this->mailboxMapper,
			$this->folderMapper,
			$this->mailAccountMapper,
			$this->imapClientFactory,
			$this->timeFactory,
			$this->dispatcher
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

	public function testSync() {
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
		];
		$status = [
			'unseen' => 10,
			'messages' => 42,
		];
		$folders[0]->method('getStatus')->willReturn($status);
		$folders[1]->method('getStatus')->willReturn($status);
		$this->folderMapper->expects($this->once())
			->method('getFolders')
			->with($account, $client)
			->willReturn($folders);
		$this->folderMapper->expects($this->once())
			->method('detectFolderSpecialUse')
			->with($folders);
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

		$this->sync->sync($account, new NullLogger());
	}

	public function testSyncStats(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$stats = new MailboxStats(42, 10, null);
		$mailbox = new Mailbox();
		$mailbox->setName('mailbox');
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatusAsObject')
			->with($client, $mailbox->getName())
			->willReturn($stats);
		$this->mailboxMapper->expects($this->once())
			->method('update')
			->with($mailbox);

		$this->sync->syncStats($account, $mailbox);

		$this->assertEquals(42, $mailbox->getMessages());
		$this->assertEquals(10, $mailbox->getUnseen());
	}
}
