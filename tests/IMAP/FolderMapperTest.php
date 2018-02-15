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

namespace OCA\Mail\Tests\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\SearchFolder;
use ChristophWurst\Nextcloud\Testing\TestCase;

class FolderMapperTest extends TestCase {

	/** @var FolderMapper */
	private $mapper;

	protected function setUp() {
		parent::setUp();

		$this->mapper = new FolderMapper();
	}

	public function testGetFoldersEmtpyAccount() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('listMailboxes')
			->with($this->equalTo('*'), $this->equalTo(Horde_Imap_Client::MBOX_ALL),
				$this->equalTo([
					'delimiter' => true,
					'attributes' => true,
					'special_use' => true,
			]))
			->willReturn([]);

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertEquals([], $folders);
	}

	public function testGetFolders() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('listMailboxes')
			->with($this->equalTo('*'), $this->equalTo(Horde_Imap_Client::MBOX_ALL),
				$this->equalTo([
					'delimiter' => true,
					'attributes' => true,
					'special_use' => true,
			]))
			->willReturn([
				[
					'mailbox' => new Horde_Imap_Client_Mailbox('INBOX'),
					'attributes' => [],
					'delimiter' => '.',
				],
				[
					'mailbox' => new Horde_Imap_Client_Mailbox('Sent'),
					'attributes' => [
						'\sent',
					],
					'delimiter' => '.',
				],
		]);
		$expected = [
			new Folder($account, new Horde_Imap_Client_Mailbox('INBOX'), [], '.'),
			new SearchFolder($account, new Horde_Imap_Client_Mailbox('INBOX'), [], '.'),
			new Folder($account, new Horde_Imap_Client_Mailbox('Sent'), ['\sent'], '.'),
		];

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertEquals($expected, $folders);
	}

	public function testBuildHierarchyWithoutPrefix() {
		$account = $this->createMock(Account::class);
		$folder1 = new Folder($account, new Horde_Imap_Client_Mailbox('test'), [], '.');
		$folder2 = new Folder($account, new Horde_Imap_Client_Mailbox('test.sub'), [], '.');
		$folder3 = new Folder($account, new Horde_Imap_Client_Mailbox('test.sub.sub'), [], '.');
		$folders = [
			clone $folder1,
			clone $folder2,
			clone $folder3,
		];
		$folder1->addFolder($folder2);
		$folder1->addFolder($folder3);
		$expected = [
			$folder1,
		];

		$result = $this->mapper->buildFolderHierarchy($folders, false);

		$this->assertEquals($expected, $result);
	}

	public function testBuildHierarchyWithPrefix() {
		$account = $this->createMock(Account::class);
		$folder1 = new Folder($account, new Horde_Imap_Client_Mailbox('INBOX'), [], '.');
		$folder2 = new Folder($account, new Horde_Imap_Client_Mailbox('INBOX.sub'), [], '.');
		$folder3 = new Folder($account, new Horde_Imap_Client_Mailbox('INBOX.sub.sub'), [], '.');
		$folder4 = new Folder($account, new Horde_Imap_Client_Mailbox('INBOX.sub.sub.sub'), [], '.');
		$folders = [
			clone $folder1,
			clone $folder2,
			clone $folder3,
			clone $folder4,
		];
		$folder2->addFolder($folder3);
		$folder2->addFolder($folder4);
		$expected = [
			$folder1,
			$folder2,
		];

		$result = $this->mapper->buildFolderHierarchy($folders, true);

		$this->assertCount(count($expected), $result);
		$this->assertEquals($expected, $result);
	}

	public function testGetFoldersStatus() {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('isSearchable')
			->willReturn(true);
		$client->expects($this->once())
			->method('status')
			->with($this->equalTo(['folder1']))
			->willReturn([
				'folder1' => [
					'total' => 123
				],
		]);
		$folders[0]->expects($this->once())
			->method('setStatus');

		$this->mapper->getFoldersStatus($folders, $client);
	}

	public function testGetFoldersStatusNoStatusReported() {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('isSearchable')
			->willReturn(true);
		$client->expects($this->once())
			->method('status')
			->with($this->equalTo(['folder1']))
			->willReturn([
				// Nothing reported for this folder
		]);
		$folders[0]->expects($this->never())
			->method('setStatus');

		$this->mapper->getFoldersStatus($folders, $client);
	}

	public function testGetFoldersStatusNotSearchable() {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('isSearchable')
			->willReturn(false);
		$client->expects($this->once())
			->method('status')
			->with($this->equalTo([]))
			->willReturn([]);
		$folders[0]->expects($this->never())
			->method('setStatus');

		$this->mapper->getFoldersStatus($folders, $client);
	}

	public function testDetectSpecialUseFromAttributes() {
		$folders = [
			$this->createMock(Folder::class),
		];
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn([
				Horde_Imap_Client::SPECIALUSE_SENT,
		]);
		$folders[0]->expects($this->once())
			->method('addSpecialUse')
			->with($this->equalTo('sent'));
		$folders[0]->expects($this->once())
			->method('getSpecialUse')
			->willReturn(['sent']);

		$this->mapper->detectFolderSpecialUse($folders);
	}

	public function testDetectSpecialUseFromFolderName() {
		$folders = [
			$this->createMock(Folder::class),
		];
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn([]);
		$folders[0]->expects($this->once())
			->method('getSpecialUse')
			->willReturn([]);
		$folders[0]->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');
		$folders[0]->expects($this->once())
			->method('getMailbox')
			->willReturn('Sent');
		$folders[0]->expects($this->once())
			->method('addSpecialUse')
			->with($this->equalTo('sent'));

		$this->mapper->detectFolderSpecialUse($folders);
	}

	public function testSortFolders() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Horde_Imap_Client_Mailbox::class);

		$folder1 = new Folder($account, $mailbox, [], '.');
		$folder1->setDisplayName('Entwürfe');
		$folder1->addSpecialUse('drafts');
		$folder2 = new Folder($account, $mailbox, [], '.');
		$folder2->setDisplayName('Eingang');
		$folder2->addSpecialUse('inbox');
		$folder3 = new Folder($account, $mailbox, [], '.');
		$folder3->setDisplayName('Other 2');
		$folder4 = new Folder($account, $mailbox, [], '.');
		$folder4->setDisplayName('Other 1');
		$folder5 = new Folder($account, $mailbox, [], '.');
		$folder5->setDisplayName('Gesendete Elemente');
		$folder5->addSpecialUse('sent');
		$folder6 = new Folder($account, $mailbox, [], '.');
		$folder6->setDisplayName('Gesendet');
		$folder6->addSpecialUse('sent');

		$folders = [
			$folder1,
			$folder2,
			$folder3,
			$folder4,
			$folder5,
			$folder6,
		];

		$this->mapper->sortFolders($folders);

		// Expected order: Inbox, Drafts, Sent1, Sent2, other 1, other 2
		$this->assertSame($folder2, $folders[0]);
		$this->assertSame($folder1, $folders[1]);
		$this->assertSame($folder6, $folders[2]);
		$this->assertSame($folder5, $folders[3]);
		$this->assertSame($folder4, $folders[4]);
		$this->assertSame($folder3, $folders[5]);
	}

}
