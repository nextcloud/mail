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

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Capability;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\MailboxStats;

class FolderMapperTest extends TestCase {
	/** @var FolderMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = new FolderMapper();
	}

	public function testGetFoldersEmtpyAccount(): void {
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

	public function testGetFoldersNonExistent(): void {
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
					'mailbox' => new Horde_Imap_Client_Mailbox('shared'),
					'attributes' => [
						'\\nonexistent',
					],
					'delimiter' => '.',
				],
			]);
		$expected = [
			new Folder(27, new Horde_Imap_Client_Mailbox('INBOX'), [], '.', null),
		];

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertEquals($expected, $folders);
	}

	public function testGetFolders(): void {
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
			new Folder(27, new Horde_Imap_Client_Mailbox('INBOX'), [], '.', null),
			new Folder(27, new Horde_Imap_Client_Mailbox('Sent'), ['\sent'], '.', null),
		];

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertEquals($expected, $folders);
	}

	public function testCreateFolder(): void {
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
					'status' => Horde_Imap_Client::STATUS_ALL,
				]))
			->willReturn([
				[
					'mailbox' => new Horde_Imap_Client_Mailbox('new'),
					'attributes' => [],
					'delimiter' => '.',
					'status' => [
						'unseen' => 0,
					],
				],
			]);

		$created = $this->mapper->createFolder($client, $account, 'new');

		$expected = new Folder(42, new Horde_Imap_Client_Mailbox('new'), [], '.', ['unseen' => 0]);
		$this->assertEquals($expected, $created);
	}

	public function testFetchFoldersAclsNoSelect(): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getAttributes')
			->willReturn(['\\NoSelect']);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->expects(self::once())
			->method('query')
			->with('ACL')
			->willReturn(true);
		$client->expects(self::never())
			->method('getMyACLRights');

		$this->mapper->fetchFolderAcls($folders, $client);
	}

	public function testFetchFoldersAcls(): void {
		$folders = [
			$this->createMock(Folder::class),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$folders[0]->expects($this->any())
			->method('getMailbox')
			->willReturn('folder1');
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability
			->expects(self::once())
			->method('query')
			->with('ACL')
			->willReturn(false);
		$client->expects(self::never())
			->method('getMyACLRights');

		$this->mapper->fetchFolderAcls($folders, $client);
	}

	public function testGetFoldersStatusAsObject(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->with(['INBOX'])
			->willReturn([
				'INBOX' => [
					'messages' => 123,
					'unseen' => 2,
				],
			]);

		$stats = $this->mapper->getFoldersStatusAsObject($client, ['INBOX']);

		self::assertArrayHasKey('INBOX', $stats);
		$expected = new MailboxStats(123, 2);
		self::assertEquals($expected, $stats['INBOX']);
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
