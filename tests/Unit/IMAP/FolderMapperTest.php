<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Capability;
use Horde_Imap_Client_Exception_ServerResponse;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\MailboxStats;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class FolderMapperTest extends TestCase {
	/** @var FolderMapper */
	private $mapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mapper = new FolderMapper($this->logger);
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
			new Folder(new Horde_Imap_Client_Mailbox('INBOX'), [], '.', null),
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
			new Folder(new Horde_Imap_Client_Mailbox('INBOX'), [], '.', null),
			new Folder(new Horde_Imap_Client_Mailbox('Sent'), ['\sent'], '.', null),
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

		$created = $this->mapper->createFolder($client, 'new');

		$expected = new Folder(new Horde_Imap_Client_Mailbox('new'), [], '.', ['unseen' => 0]);
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

	public function testFetchFolderAclsSkipsInaccessibleMailbox(): void {
		$inaccessibleFolder = $this->createMock(Folder::class);
		$inaccessibleFolder->method('getAttributes')->willReturn([]);
		$inaccessibleFolder->method('getMailbox')->willReturn('inaccessible');
		$inaccessibleFolder->expects($this->once())
			->method('setMyAcls')
			->with(null);
		$normalFolder = $this->createMock(Folder::class);
		$normalFolder->method('getAttributes')->willReturn([]);
		$normalFolder->method('getMailbox')->willReturn('INBOX');
		$normalFolder->expects($this->once())
			->method('setMyAcls')
			->with('lrs');

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client->method('__get')->with('capability')->willReturn($capability);
		$capability->method('query')->with('ACL')->willReturn(true);

		$client->expects($this->exactly(2))
			->method('getMyACLRights')
			->willReturnCallback(function (string $mailbox) {
				if ($mailbox === 'inaccessible') {
					throw $this->createMock(Horde_Imap_Client_Exception_ServerResponse::class);
				}
				return 'lrs';
			});
		$this->logger->expects($this->once())
			->method('debug');

		$this->mapper->fetchFolderAcls([$inaccessibleFolder, $normalFolder], $client);
	}

	public function testGetFolderStatus(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->with('INBOX', Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UNSEEN)
			->willReturn([
				'messages' => 123,
				'unseen' => 2,
			]);

		$stats = $this->mapper->getFolderStatus($client, 'INBOX');

		self::assertEquals(new MailboxStats(123, 2), $stats);
	}

	public function testGetFolderStatusNullStats(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->with('INBOX', Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UNSEEN)
			->willReturn([
				'messages' => null,
				'unseen' => 2,
			]);

		$stats = $this->mapper->getFolderStatus($client, 'INBOX');

		self::assertNull($stats);
	}

	public function testGetFolderStatusInaccessibleMailbox(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects($this->once())
			->method('status')
			->willThrowException($this->createMock(Horde_Imap_Client_Exception_ServerResponse::class));
		$this->logger->expects($this->once())
			->method('debug');

		$stats = $this->mapper->getFolderStatus($client, 'inaccessible');

		self::assertNull($stats);
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
