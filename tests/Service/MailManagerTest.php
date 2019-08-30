<?php declare(strict_types=1);

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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Service\MailManager;
use PHPUnit\Framework\MockObject\MockObject;

class MailManagerTest extends TestCase {

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MailboxSync|MockObject */
	private $mailboxSync;

	/** @var FolderMapper|MockObject */
	private $folderMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var Synchronizer|MockObject */
	private $sync;

	/** @var MailManager */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->sync = $this->createMock(Synchronizer::class);

		$this->manager = new MailManager(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->mailboxSync,
			$this->folderMapper,
			$this->sync,
			$this->messageMapper
		);
	}

	public function testGetFolders() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailboxes = [
			$this->createMock(Mailbox::class),
			$this->createMock(Mailbox::class),
		];
		$folders = [
			$this->createMock(Folder::class),
			$this->createMock(Folder::class),
		];
		$mailboxes[0]->method('toFolder')->willReturn($folders[0]);
		$mailboxes[1]->method('toFolder')->willReturn($folders[1]);
		$this->mailboxSync->expects($this->once())
			->method('sync')
			->with($this->equalTo($account));
		$this->mailboxMapper->expects($this->once())
			->method('findAll')
			->with($this->equalTo($account))
			->willReturn($mailboxes);

		$result = $this->manager->getFolders($account);

		$this->assertSame($folders, $result);
	}

	public function testCreateFolder() {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$folder =$this->createMock(Folder::class);
		$this->folderMapper->expects($this->once())
			->method('createFolder')
			->with($this->equalTo($client), $this->equalTo($account), $this->equalTo('new'))
			->willReturn($folder);
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatus')
			->with($this->equalTo([$folder]));
		$this->folderMapper->expects($this->once())
			->method('detectFolderSpecialUse')
			->with($this->equalTo([$folder]));

		$created = $this->manager->createFolder($account, 'new');

		$this->assertEquals($folder, $created);
	}

	public function testGetFolderStats() {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$stats = $this->createMock(FolderStats::class);
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatusAsObject')
			->with($this->equalTo($client), $this->equalTo('INBOX'))
			->willReturn($stats);

		$actual = $this->manager->getFolderStats($account, 'INBOX');

		$this->assertEquals($stats, $actual);
	}

	public function testSync() {
		$account = $this->createMock(Account::class);
		$syncRequest = $this->createMock(Request::class);
		$syncResonse = $this->createMock(Response::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$this->sync->expects($this->once())
			->method('sync')
			->with($client, $syncRequest)
			->willReturn($syncResonse);

		$this->manager->syncMessages($account, $syncRequest);
	}

}
