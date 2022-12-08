<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\Unit\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Data_Acl;
use Horde_Imap_Client_Data_Capability;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\MailboxStats;
use ChristophWurst\Nextcloud\Testing\TestCase;

class FolderMapperTest extends TestCase {
	/** @var FolderMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = new FolderMapper();
	}

	public function testGetFoldersEmtpyAccount() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('listMailboxes')
			->with($this->equalTo('*'), $this->equalTo(Horde_Imap_Client::MBOX_ALL_SUBSCRIBED),
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
		$account->method('getId')->willReturn(27);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('listMailboxes')
			->with($this->equalTo('*'), $this->equalTo(Horde_Imap_Client::MBOX_ALL_SUBSCRIBED),
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
			new Folder(27, new Horde_Imap_Client_Mailbox('INBOX'), [], '.'),
			new Folder(27, new Horde_Imap_Client_Mailbox('Sent'), ['\sent'], '.'),
		];

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertEquals($expected, $folders);
	}

	public function testCreateFolder() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(42);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('createMailbox')
			->with($this->equalTo('new'));
		$client->expects($this->once())
			->method('listMailboxes')
			->with($this->equalTo('new'), $this->equalTo(Horde_Imap_Client::MBOX_ALL_SUBSCRIBED),
				$this->equalTo([
					'delimiter' => true,
					'attributes' => true,
					'special_use' => true,
				]))
			->willReturn([
				[
					'mailbox' => new Horde_Imap_Client_Mailbox('new'),
					'attributes' => [],
					'delimiter' => '.',
				],
			]);

		$created = $this->mapper->createFolder($client, $account, 'new');

		$expected = new Folder(42, new Horde_Imap_Client_Mailbox('new'), [], '.');
		$this->assertEquals($expected, $created);
	}

	public function testGetFoldersStatus(): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn([]);
		$client->expects($this->once())
			->method('status')
			->with($this->equalTo(['folder1']))
			->willReturn([
				'folder1' => [
					'total' => 123
				],
			]);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(false);
		$folders[0]->expects($this->once())
			->method('setStatus');

		$this->mapper->getFoldersStatus($folders, $client);
	}

	public function testGetFoldersStatusNoStatusReported(): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn([]);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(false);
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

	public function testGetFoldersStatusNotSearchable(): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn(['\\noselect']);
		$client->expects($this->once())
			->method('status')
			->with($this->equalTo([]))
			->willReturn([]);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(false);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(false);
		$folders[0]->expects($this->never())
			->method('setStatus');

		$this->mapper->getFoldersStatus($folders, $client);
	}

	public function testGetFoldersStatusAsObject(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->with('INBOX')
			->willReturn([
				'messages' => 123,
				'unseen' => 2,
			]);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(false);

		$stats = $this->mapper->getFoldersStatusAsObject($client, 'INBOX');

		$expected = new MailboxStats(123, 2, null);
		$this->assertEquals($expected, $stats);
	}

	public function testGetFoldersStatusAndMyAcls(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->with('INBOX')
			->willReturn([
				'messages' => 123,
				'unseen' => 2,
			]);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->method('query')
			->with('ACL')
			->willReturn(true);
		$acl = $this->createMock(Horde_Imap_Client_Data_Acl::class);
		$client->expects(self::once())
			->method('getMyACLRights')
			->willReturn($acl);
		$acl
			->method('__toString')
			->willReturn('kthxbye');

		$stats = $this->mapper->getFoldersStatusAsObject($client, 'INBOX');

		$expected = new MailboxStats(123, 2, 'kthxbye');
		$this->assertEquals($expected, $stats);
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

	/**
	 * @dataProvider dataDetectSpecialUseFromFolderName
	 */
	public function testDetectSpecialUseFromFolderName(?string $delimiter, int $countGetDelimiter): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$folders[0]->expects($this->once())
			->method('getAttributes')
			->willReturn([]);
		$folders[0]->expects($this->once())
			->method('getSpecialUse')
			->willReturn([]);
		$folders[0]->expects($this->exactly($countGetDelimiter))
			->method('getDelimiter')
			->willReturn($delimiter);
		$folders[0]->expects($this->once())
			->method('getMailbox')
			->willReturn('Sent');
		$folders[0]->expects($this->once())
			->method('addSpecialUse')
			->with($this->equalTo('sent'));

		$this->mapper->detectFolderSpecialUse($folders);
	}

	public function dataDetectSpecialUseFromFolderName(): array {
		return [
			'delimiter .' => ['.', 2],
			'delimiter null' => [null, 1],
		];
	}
}
