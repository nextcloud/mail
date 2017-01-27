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

namespace OCA\Mail\Tests\Service;

use Horde_Imap_Client;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\SearchFolder;
use OCA\Mail\Service\FolderMapper;
use Test\TestCase;

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

	public function testBuildHierarchy() {
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

		$result = $this->mapper->buildFolderHierarchy($folders);

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
		$folder1 = $this->createMock(Folder::class);
		$folder1->expects($this->any())
			->method('getSpecialUse')
			->willReturn(['drafts']);
		$folder1->expects($this->any())
			->method('getDisplayName')
			->willReturn('Entwürfe');
		$folder2 = $this->createMock(Folder::class);
		$folder2->expects($this->any())
			->method('getSpecialUse')
			->willReturn(['inbox']);
		$folder2->expects($this->any())
			->method('getDisplayName')
			->willReturn('Eingang');
		$folder3 = $this->createMock(Folder::class);
		$folder3->expects($this->any())
			->method('getDisplayName')
			->willReturn('Other 2');
		$folder4 = $this->createMock(Folder::class);
		$folder4->expects($this->any())
			->method('getDisplayName')
			->willReturn('Other 1');
		$folder5 = $this->createMock(Folder::class);
		$folder5->expects($this->any())
			->method('getSpecialUse')
			->willReturn(['sent']);
		$folder5->expects($this->any())
			->method('getDisplayName')
			->willReturn('Gesendete Elemente');
		$folder6 = $this->createMock(Folder::class);
		$folder6->expects($this->any())
			->method('getSpecialUse')
			->willReturn(['sent']);
		$folder6->expects($this->any())
			->method('getDisplayName')
			->willReturn('Gesendet');

		$folders = [
			$folder1,
			$folder2,
			$folder3,
			$folder4,
			$folder5,
			$folder6,
		];

		$result = $this->mapper->sortFolders($folders);

		// Expected order: Inbox, Drafts, Sent1, Sent2, other
		$this->assertSame($folder2, $result[0]);
		$this->assertSame($folder1, $result[1]);
		$this->assertSame($folder6, $result[2]);
		$this->assertSame($folder5, $result[3]);
		$this->assertSame($folder4, $result[4]);
		$this->assertSame($folder3, $result[5]);
	}

}
