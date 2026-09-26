<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\MessageTagsMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MailManagerTest extends TestCase {
	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MailboxSync|MockObject */
	private $mailboxSync;

	/** @var FolderMapper|MockObject */
	private $folderMapper;

	/** @var ImapMessageMapper|MockObject */
	private $imapMessageMapper;

	/** @var DbMessageMapper|MockObject */
	private $dbMessageMapper;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var MailManager */
	private $manager;

	/** @var MockObject|LoggerInterface */
	private $logger;

	/** @var MockObject|TagMapper */
	private $tagMapper;

	/** @var MessageTagsMapper|MockObject */
	private $messageTagsMapper;

	/** @var ThreadMapper|MockObject */
	private $threadMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->imapMessageMapper = $this->createMock(ImapMessageMapper::class);
		$this->dbMessageMapper = $this->createMock(DbMessageMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->tagMapper = $this->createMock(TagMapper::class);
		$this->messageTagsMapper = $this->createMock(MessageTagsMapper::class);
		$this->threadMapper = $this->createMock(ThreadMapper::class);

		$this->manager = new MailManager(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->mailboxSync,
			$this->folderMapper,
			$this->imapMessageMapper,
			$this->dbMessageMapper,
			$this->eventDispatcher,
			$this->logger,
			$this->tagMapper,
			$this->messageTagsMapper,
			$this->threadMapper,
			new ImapFlag(),
		);
	}

	public function testGetFolders() {
		/** @var Account|MockObject $account */
		$account = $this->createStub(Account::class);
		$mailboxes = [
			$this->createMock(Mailbox::class),
			$this->createMock(Mailbox::class),
		];
		$this->mailboxSync->expects($this->once())
			->method('sync')
			->with($this->equalTo($account));
		$this->mailboxMapper->expects($this->once())
			->method('findAll')
			->with($this->equalTo($account))
			->willReturn($mailboxes);

		$result = $this->manager->getMailboxes($account);

		$this->assertSame($mailboxes, $result);
	}

	public function testCreateFolder() {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$folder = $this->createStub(Folder::class);
		$this->folderMapper->expects($this->once())
			->method('createFolder')
			->with($this->equalTo($client), $this->equalTo('new'))
			->willReturn($folder);
		$this->folderMapper->expects($this->once())
			->method('fetchFolderAcls')
			->with($this->equalTo([$folder]));
		$this->folderMapper->expects($this->once())
			->method('detectFolderSpecialUse')
			->with($this->equalTo([$folder]));
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'new')
			->willReturn($mailbox);

		$created = $this->manager->createMailbox($account, 'new');

		$this->assertEquals($mailbox, $created);
	}

	public function testDeleteMessageSourceFolderNotFound(): void {
		/** @var Account|MockObject $account */
		$account = $this->createStub(Account::class);
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willThrowException(new DoesNotExistException(''));
		$this->expectException(ServiceException::class);

		$this->manager->deleteMessage(
			$account,
			'INBOX',
			123
		);
	}

	public function testDeleteMessageTrashMailboxNotFound(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mailbox);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(''));
		$this->expectException(ServiceException::class);

		$this->manager->deleteMessage(
			$account,
			'INBOX',
			123
		);
	}

	public function testDeleteMessage(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$inbox = new Mailbox();
		$inbox->setName('INBOX');
		$trash = new Mailbox();
		$trash->setName('Trash');
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($inbox);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->once())
			->method('move')
			->with(
				$client,
				'INBOX',
				123,
				'Trash'
			);

		$this->manager->deleteMessage(
			$account,
			'INBOX',
			123
		);
	}

	public function testExpungeMessage(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$source = new Mailbox();
		$source->setName('Trash');
		$trash = new Mailbox();
		$trash->setName('Trash');
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'Trash')
			->willReturn($source);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->once())
			->method('expunge')
			->with(
				$client,
				'Trash',
				123
			);

		$this->manager->deleteMessage(
			$account,
			'Trash',
			123
		);
	}

	public function testDeleteMessageTrashMailboxIdNull(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(null);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mailbox);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('logout');
		$this->expectException(TrashMailboxNotSetException::class);

		$this->manager->deleteMessage(
			$account,
			'INBOX',
			123
		);
	}

	public function testDeleteMessageWithClientTrashMailboxIdNull(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(null);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn ($event) => $event instanceof BeforeMessageDeletedEvent));
		$this->imapMessageMapper->expects($this->never())
			->method('expunge');
		$this->imapMessageMapper->expects($this->never())
			->method('move');
		$this->expectException(TrashMailboxNotSetException::class);

		$this->manager->deleteMessageWithClient(
			$account,
			$mailbox,
			123,
			$client
		);
	}

	public function testDeleteMessageWithClientExpungeThrowsException(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$trash = new Mailbox();
		$trash->setName('Trash');
		$source = new Mailbox();
		$source->setName('Trash');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$this->imapMessageMapper->expects($this->once())
			->method('expunge')
			->willThrowException(new Horde_Imap_Client_Exception('expunge failed'));
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn ($event) => $event instanceof BeforeMessageDeletedEvent));
		$this->expectException(Horde_Imap_Client_Exception::class);

		$this->manager->deleteMessageWithClient(
			$account,
			$source,
			123,
			$client
		);
	}

	public function testDeleteMessageWithClientMoveThrowsException(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$trash = new Mailbox();
		$trash->setName('Trash');
		$inbox = new Mailbox();
		$inbox->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$this->imapMessageMapper->expects($this->once())
			->method('move')
			->willThrowException(new Horde_Imap_Client_Exception('move failed'));
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn ($event) => $event instanceof BeforeMessageDeletedEvent));
		$this->expectException(Horde_Imap_Client_Exception::class);

		$this->manager->deleteMessageWithClient(
			$account,
			$inbox,
			123,
			$client
		);
	}

	public function testSetCustomFlagNoIMAPCapabilities(): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->mailboxMapper->method('find')
			->willReturn($mailbox);
		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->never())
			->method('addFlag');
		$this->imapMessageMapper->expects($this->never())
			->method('removeFlag');

		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, true);
		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, false);
	}

	public function testSetCustomFlagWithIMAPCapabilities(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->mailboxMapper->method('find')
			->willReturn($mailbox);
		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('status')
			->willReturn([ 'permflags' => [ '11' => "\*" ] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('addFlag');

		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, true);
	}

	public function testUnsetCustomFlagWithIMAPCapabilities(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->mailboxMapper->method('find')
			->willReturn($mailbox);
		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('status')
			->willReturn([ 'permflags' => [ '11' => "\*" ] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('removeFlag');

		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, false);
	}

	public function testFilterFlagsWithSystemFlags(): void {
		$account = $this->createStub(Account::class);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$flags = [
			'seen' => [\Horde_Imap_Client::FLAG_SEEN],
			'answered' => [\Horde_Imap_Client::FLAG_ANSWERED],
			'flagged' => [\Horde_Imap_Client::FLAG_FLAGGED],
			'deleted' => [\Horde_Imap_Client::FLAG_DELETED],
			'draft' => [\Horde_Imap_Client::FLAG_DRAFT],
			'recent' => [\Horde_Imap_Client::FLAG_RECENT],
		];

		// test all system flags
		foreach ($flags as $k => $flag) {
			$this->assertEquals($this->manager->filterFlags($client, $account, $k, 'INBOX'), $flags[$k]);
		}
	}

	public function testFilterFlagsWithDefinedKeyword() {
		$account = $this->createStub(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->exactly(2))
			->method('status')
			->willReturn(['permflags' => ['\seen', '$junk', '$notjunk']]);

		// test keyword supported
		$this->assertEquals(['$junk'], $this->manager->filterFlags($client, $account, '$junk', 'INBOX'));
		// test keyword unsupported
		$this->assertEquals([], $this->manager->filterFlags($client, $account, '$autojunk', 'INBOX'));
	}

	public function testFilterFlagsWithCustomKeyword() {
		$account = $this->createStub(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->exactly(2))
			->method('status')
			->willReturnOnConsecutiveCalls(
				['permflags' => ['\seen', '$junk', '$notjunk', '\*']],
				['permflags' => ['\seen', '$junk', '$notjunk']],
			);

		// test custom keyword supported
		$this->assertEquals([Tag::LABEL_IMPORTANT], $this->manager->filterFlags($client, $account, Tag::LABEL_IMPORTANT, 'INBOX'));
		// test custom keyword unsupported
		$this->assertEquals([], $this->manager->filterFlags($client, $account, Tag::LABEL_IMPORTANT, 'INBOX'));
	}

	public function testFilterFlagsNoCapabilities() {
		$account = $this->createStub(Account::class);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);

		$this->assertEquals([], $this->manager->filterFlags($client, $account, Tag::LABEL_IMPORTANT, 'INBOX'));
	}

	public function testIsPermflagsEnabledTrue(): void {
		$account = $this->createStub(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ '11' => "\*"] ]);

		$this->assertTrue($this->manager->isPermflagsEnabled($client, $account, 'INBOX'));
	}

	public function testIsPermflagsEnabledFalse(): void {
		$account = $this->createStub(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->once())
			->method('status')
			->willReturn([]);

		$this->assertFalse($this->manager->isPermflagsEnabled($client, $account, 'INBOX'));
	}

	public function testRemoveFlag(): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$mb = new Mailbox();
		$mb->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$this->imapMessageMapper->expects($this->never())
			->method('addFlag');
		$this->imapMessageMapper->expects($this->once())
			->method('removeFlag')
			->with($client, $mb, [123], '\\seen');

		$this->manager->flagMessage($account, 'INBOX', 123, 'seen', false);
	}

	public function testFlagMessagesWithoutUids(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$this->imapClientFactory->expects($this->never())
			->method('getClient');

		$this->manager->flagMessages($account, $mailbox, [], 'seen', true);
	}

	public function testFlagMessagesInChunks(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$uids = range(1, 1200);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$chunks = [];
		$this->imapMessageMapper->expects($this->exactly(3))
			->method('addFlag')
			->willReturnCallback(function ($c, $mb, array $chunk, string $flag) use (&$chunks, $client, $mailbox): void {
				$this->assertSame($client, $c);
				$this->assertSame($mailbox, $mb);
				$this->assertSame(\Horde_Imap_Client::FLAG_SEEN, $flag);
				$chunks[] = $chunk;
			});
		$this->imapMessageMapper->expects($this->never())
			->method('removeFlag');
		$client->expects($this->once())
			->method('logout');

		$this->manager->flagMessages($account, $mailbox, $uids, 'seen', true);

		$this->assertSame($uids, array_merge(...$chunks));
		$this->assertCount(500, $chunks[0]);
	}

	public function testUnflagMessages(): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->never())
			->method('addFlag');
		$this->imapMessageMapper->expects($this->once())
			->method('removeFlag')
			->with($client, $mailbox, [1, 2], \Horde_Imap_Client::FLAG_FLAGGED);

		$this->manager->flagMessages($account, $mailbox, [1, 2], 'flagged', false);
	}

	public function testFlagMessagesWrapsImapErrors(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->method('addFlag')
			->willThrowException(new \Horde_Imap_Client_Exception('connection lost'));
		$client->expects($this->once())
			->method('logout');
		$this->expectException(ServiceException::class);

		$this->manager->flagMessages($account, $mailbox, [1], 'seen', true);
	}

	private function mailbox(int $id, string $name): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId($id);
		$mailbox->setName($name);
		return $mailbox;
	}

	private function accountWithTrash(?int $trashMailboxId): Account&MockObject {
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId($trashMailboxId);
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		return $account;
	}

	public function testMoveMessagesWithoutUids(): void {
		$account = $this->createStub(Account::class);
		$this->imapClientFactory->expects($this->never())
			->method('getClient');

		$this->manager->moveMessages($account, $this->mailbox(1, 'INBOX'), [], $this->mailbox(2, 'Archive'));
	}

	public function testMoveMessagesInChunks(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$inbox = $this->mailbox(1, 'INBOX');
		$archive = $this->mailbox(2, 'Archive');
		$uids = range(1, 700);
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$moved = [];
		$this->imapMessageMapper->expects($this->exactly(2))
			->method('moveMessages')
			->willReturnCallback(function ($c, string $source, array $chunk, string $destination) use (&$moved): array {
				$this->assertSame('INBOX', $source);
				$this->assertSame('Archive', $destination);
				$moved[] = $chunk;
				return array_combine($chunk, array_map(static fn (int $uid): int => $uid + 1000, $chunk));
			});
		$cleared = [];
		$this->dbMessageMapper->expects($this->exactly(2))
			->method('deleteByUid')
			->willReturnCallback(function (Mailbox $mailbox, int ...$chunk) use (&$cleared, $inbox): void {
				$this->assertSame($inbox, $mailbox);
				$cleared[] = $chunk;
			});
		$client->expects($this->once())
			->method('logout');

		$mapping = $this->manager->moveMessages($account, $inbox, $uids, $archive);

		$this->assertSame($moved, $cleared);
		$this->assertCount(700, $mapping);
		$this->assertSame(1700, $mapping[700]);
		$this->assertSame($uids, array_merge(...$moved));
	}

	public function testMoveMessagesKeepsCacheOfFailedChunk(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createStub(Account::class);
		$inbox = $this->mailbox(1, 'INBOX');
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->method('moveMessages')
			->willReturnCallback(function ($c, string $source, array $chunk): array {
				if ($chunk[0] > 500) {
					throw new ServiceException('connection lost');
				}
				return [];
			});
		$cleared = [];
		$this->dbMessageMapper->method('deleteByUid')
			->willReturnCallback(function (Mailbox $mailbox, int ...$chunk) use (&$cleared): void {
				$cleared = array_merge($cleared, $chunk);
			});
		$client->expects($this->once())
			->method('logout');

		try {
			$this->manager->moveMessages($account, $inbox, range(1, 700), $this->mailbox(2, 'Archive'));
			$this->fail('Expected exception');
		} catch (ServiceException $e) {
		}

		$this->assertSame(range(1, 500), $cleared);
	}

	public function testDeleteMessagesMovesToTrash(): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->accountWithTrash(9);
		$inbox = $this->mailbox(1, 'INBOX');
		$this->mailboxMapper->method('findById')
			->with(9)
			->willReturn($this->mailbox(9, 'Trash'));
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->once())
			->method('moveMessages')
			->with($client, 'INBOX', [1, 2], 'Trash');
		$this->imapMessageMapper->expects($this->never())
			->method('expungeMessages');
		$this->dbMessageMapper->expects($this->once())
			->method('deleteByUid')
			->with($inbox, 1, 2);

		$this->manager->deleteMessages($account, $inbox, [1, 2]);
	}

	public function testDeleteMessagesExpungesInTrash(): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$account = $this->accountWithTrash(9);
		$trash = $this->mailbox(9, 'Trash');
		$this->mailboxMapper->method('findById')
			->willReturn($this->mailbox(9, 'Trash'));
		$this->imapClientFactory->method('getClient')
			->willReturn($client);
		$this->imapMessageMapper->expects($this->never())
			->method('moveMessages');
		$this->imapMessageMapper->expects($this->once())
			->method('expungeMessages')
			->with($client, 'Trash', [1, 2]);
		$this->dbMessageMapper->expects($this->once())
			->method('deleteByUid')
			->with($trash, 1, 2);

		$this->manager->deleteMessages($account, $trash, [1, 2]);
	}

	public function testDeleteMessagesWithoutTrash(): void {
		$account = $this->accountWithTrash(null);
		$this->imapClientFactory->expects($this->never())
			->method('getClient');
		$this->expectException(TrashMailboxNotSetException::class);

		$this->manager->deleteMessages($account, $this->mailbox(1, 'INBOX'), [1]);
	}

	public function testDeleteMessagesTrashNotFound(): void {
		$account = $this->accountWithTrash(9);
		$this->mailboxMapper->method('findById')
			->willThrowException(new DoesNotExistException(''));
		$this->expectException(ServiceException::class);

		$this->manager->deleteMessages($account, $this->mailbox(1, 'INBOX'), [1]);
	}

	public function testDeleteMessagesWithoutUids(): void {
		$account = $this->accountWithTrash(9);
		$this->mailboxMapper->expects($this->never())
			->method('findById');

		$this->manager->deleteMessages($account, $this->mailbox(1, 'INBOX'), []);
	}

	public function testTagMessagesByUidsInChunks(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createConfiguredMock(Account::class, ['getUserId' => 'john']);
		$inbox = $this->mailbox(1, 'INBOX');
		$tag = new Tag();
		$tag->setImapLabel('$label1');
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$client->method('status')
			->willReturn(['permflags' => ['\\*']]);
		$loaded = [];
		$this->dbMessageMapper->expects($this->exactly(2))
			->method('findByUids')
			->willReturnCallback(function (Mailbox $mailbox, array $uids) use (&$loaded): array {
				$loaded[] = $uids;
				return array_map(static function (int $uid): \OCA\Mail\Db\Message {
					$message = new \OCA\Mail\Db\Message();
					$message->setUid($uid);
					$message->setMessageId("<$uid@example.com>");
					return $message;
				}, $uids);
			});
		$this->imapMessageMapper->expects($this->exactly(2))
			->method('addFlag');
		$this->tagMapper->expects($this->exactly(600))
			->method('tagMessage');
		$client->expects($this->once())
			->method('logout');

		$this->manager->tagMessagesByUids($account, $inbox, range(1, 600), $tag, true);

		$this->assertSame(range(1, 600), array_merge(...$loaded));
	}

	public function testTagMessagesByUidsWithoutUids(): void {
		$this->imapClientFactory->expects($this->never())
			->method('getClient');

		$this->manager->tagMessagesByUids($this->createStub(Account::class), $this->mailbox(1, 'INBOX'), [], new Tag(), true);
	}

	public function testTagMessage(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(123);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$mb = new Mailbox();
		$mb->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ '11' => "\*"] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('addFlag')
			->with($client, $mb, [123], Tag::LABEL_IMPORTANT);
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('test');
		$this->manager->tagMessage($account, 'INBOX', $message, $tag, true);
	}

	public function testUntagMessage(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(123);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$mb = new Mailbox();
		$mb->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ '11' => "\*"] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('removeFlag')
			->with($client, $mb, [123], Tag::LABEL_IMPORTANT);
		$this->imapMessageMapper->expects($this->never())
			->method('addFlag');
		$account->expects($this->never())
			->method('getUserId')
			->willReturn('test');
		$this->manager->tagMessage($account, 'INBOX', $message, $tag, false);
	}

	public function testTagNoIMAPCapabilities(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(123);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);

		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$mb = new Mailbox();
		$mb->setName('INBOX');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$client->expects($this->once())
			->method('status')
			->willReturn([]);
		$this->imapMessageMapper->expects($this->never())
			->method('removeFlag');
		$this->imapMessageMapper->expects($this->never())
			->method('addFlag');
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('test');
		$this->manager->tagMessage($account, 'INBOX', $message, $tag, true);
	}

	public function testGetThread(): void {
		$account = $this->createStub(Account::class);
		$threadRootId = '<some.message.id@localhost>';

		$this->dbMessageMapper->expects($this->once())
			->method('findThread')
			->with($account, $threadRootId);

		$this->manager->getThread($account, $threadRootId);
	}

	public function testGetMailAttachments(): void {
		$account = $this->createMock(Account::class);
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('user');
		$attachments = [
			new Attachment(
				null,
				'cat.png',
				'image/png',
				'abcdefg',
				7,
				null,
				null,
			),
		];
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$mailbox = new Mailbox();
		$mailbox->setName('Inbox');
		$message = new Message();
		$message->setUid(123);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$this->imapMessageMapper->expects($this->once())
			->method('getAttachments')
			->with(
				$client,
				$mailbox->getName(),
				$message->getUid()
			)->willReturn($attachments);
		$result = $this->manager->getMailAttachments($account, $mailbox, $message);
		$this->assertEquals($attachments, $result);
	}

	public function testCreateTag(): void {
		$this->tagMapper->expects($this->once())
			->method('getTagByImapLabel')
			->willThrowException(new DoesNotExistException('Computer says no'));
		$this->tagMapper->expects($this->once())
			->method('insert')
			->willReturnCallback(static fn (Tag $tag) => $tag);

		$tag = $this->manager->createTag('Hello Hello 👋', '#0082c9', 'admin');

		self::assertEquals('admin', $tag->getUserId());
		self::assertEquals('Hello Hello 👋', $tag->getDisplayName());
		self::assertEquals('$hello_hello_&2d3csw-', $tag->getImapLabel());
		self::assertEquals('#0082c9', $tag->getColor());
	}

	public function testCreateTagSameImapLabel(): void {
		$existingTag = new Tag();
		$existingTag->setUserId('admin');
		$existingTag->setDisplayName('Hello Hello Hello 👋');
		$existingTag->setImapLabel('Hello_Hello_&2D3cSw-');
		$existingTag->setColor('#0082c9');

		$this->tagMapper->expects($this->once())
			->method('getTagByImapLabel')
			->willReturn($existingTag);
		$this->tagMapper->expects($this->never())
			->method('insert');

		$tag = $this->manager->createTag('Hello Hello 👋', '#e9322d', 'admin');

		self::assertEquals('admin', $tag->getUserId());
		self::assertEquals('Hello Hello Hello 👋', $tag->getDisplayName());
		self::assertEquals('Hello_Hello_&2D3cSw-', $tag->getImapLabel());
		self::assertEquals('#0082c9', $tag->getColor());
	}

	public function testCreateTagForFollowUp(): void {
		$this->tagMapper->expects(self::once())
			->method('getTagByImapLabel')
			->willThrowException(new DoesNotExistException('Computer says no'));
		$this->tagMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag) {
				self::assertEquals('admin', $tag->getUserId());
				self::assertEquals('Follow up', $tag->getDisplayName());
				self::assertEquals('$follow_up', $tag->getImapLabel());
				self::assertEquals('#d77000', $tag->getColor());
				return $tag;
			});

		$tag = $this->manager->createTag('Follow up', '#d77000', 'admin');

		self::assertEquals('admin', $tag->getUserId());
		self::assertEquals('Follow up', $tag->getDisplayName());
		self::assertEquals('$follow_up', $tag->getImapLabel());
		self::assertEquals('#d77000', $tag->getColor());
	}

	public function testUpdateTag(): void {
		$existingTag = new Tag();
		$existingTag->setId(100);
		$existingTag->setUserId('admin');
		$existingTag->setDisplayName('Hello Hello Hello 👋');
		$existingTag->setImapLabel('Hello_Hello_&2D3cSw-');
		$existingTag->setColor('#0082c9');

		$this->tagMapper->expects($this->once())
			->method('getTagForUser')
			->willReturn($existingTag);
		$this->tagMapper->expects($this->once())
			->method('update')
			->willReturnCallback(static fn (Tag $tag) => $tag);

		$tag = $this->manager->updateTag(100, 'Hello Hello 👋', '#0082c9', 'admin');

		self::assertEquals('admin', $tag->getUserId());
		self::assertEquals('Hello Hello 👋', $tag->getDisplayName());
		self::assertEquals('Hello_Hello_&2D3cSw-', $tag->getImapLabel());
		self::assertEquals('#0082c9', $tag->getColor());
	}

	public function testUpdateTagUnknownTag(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('Tag not found');

		$this->tagMapper->expects($this->once())
			->method('getTagForUser')
			->willThrowException(new DoesNotExistException('Computer says no'));
		$this->tagMapper->expects($this->never())
			->method('update');

		$this->manager->updateTag(100, 'Hello Hello 👋', '#0082c9', 'admin');
	}

	public function testMoveInbox(): void {
		$srcMailboxId = 20;
		$dstMailboxId = 80;
		$threadRootId = 'some-thread-root-id-1';
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$account = new Account($mailAccount);
		$srcMailbox = new Mailbox();
		$srcMailbox->setId($srcMailboxId);
		$srcMailbox->setAccountId($mailAccount->getId());
		$srcMailbox->setName('INBOX');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('find')
			->with($account, $srcMailbox->getName())
			->willReturn($srcMailbox);
		$this->threadMapper
			->expects(self::once())
			->method('findMessageUidsAndMailboxNamesByAccountAndThreadRoot')
			->with($mailAccount, $threadRootId, false)
			->willReturn([
				['messageUid' => 200, 'mailboxName' => 'INBOX'],
				['messageUid' => 300, 'mailboxName' => 'INBOX'],
			]);
		$dstMailbox = new Mailbox();
		$dstMailbox->setId($dstMailboxId);
		$dstMailbox->setAccountId($mailAccount->getId());
		$dstMailbox->setName('Trash');

		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('move');
		$this->eventDispatcher
			->expects(self::exactly(2))
			->method('dispatch');

		$this->manager->moveThread(
			$account,
			$srcMailbox,
			$account,
			$dstMailbox,
			$threadRootId
		);
	}

	public function testMoveTrash(): void {
		$srcMailboxId = 20;
		$dstMailboxId = 80;
		$threadRootId = 'some-thread-root-id-1';
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId($srcMailboxId);
		$account = new Account($mailAccount);
		$srcMailbox = new Mailbox();
		$srcMailbox->setId($srcMailboxId);
		$srcMailbox->setAccountId($mailAccount->getId());
		$srcMailbox->setName('Trash');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('find')
			->with($account, $srcMailbox->getName())
			->willReturn($srcMailbox);
		$this->threadMapper
			->expects(self::once())
			->method('findMessageUidsAndMailboxNamesByAccountAndThreadRoot')
			->with($mailAccount, $threadRootId, true)
			->willReturn([
				['messageUid' => 200, 'mailboxName' => 'Trash'],
				['messageUid' => 300, 'mailboxName' => 'Trash'],
			]);
		$dstMailbox = new Mailbox();
		$dstMailbox->setId($dstMailboxId);
		$dstMailbox->setAccountId($mailAccount->getId());
		$dstMailbox->setName('INBOX');

		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('move');
		$this->eventDispatcher
			->expects(self::exactly(2))
			->method('dispatch');

		$this->manager->moveThread(
			$account,
			$srcMailbox,
			$account,
			$dstMailbox,
			$threadRootId
		);
	}

	public function testDeleteInbox(): void {
		$mailboxId = 20;
		$trashMailboxId = 80;
		$threadRootId = 'some-thread-root-id-1';
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId($trashMailboxId);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId($mailAccount->getId());
		$mailbox->setName('INBOX');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('find')
			->with($account, $mailbox->getName())
			->willReturn($mailbox);
		$this->threadMapper
			->expects(self::once())
			->method('findMessageUidsAndMailboxNamesByAccountAndThreadRoot')
			->with($mailAccount, $threadRootId, false)
			->willReturn([
				['messageUid' => 200, 'mailboxName' => 'INBOX'],
				['messageUid' => 300, 'mailboxName' => 'INBOX'],
			]);
		$trashMailbox = new Mailbox();
		$trashMailbox->setId($trashMailboxId);
		$trashMailbox->setAccountId($mailAccount->getId());
		$trashMailbox->setName('Trash');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('findById')
			->with($trashMailbox->getId())
			->willReturn($trashMailbox);
		$this->imapClientFactory
			->expects(self::exactly(2))
			->method('getClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('move');
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatchTyped');

		$this->manager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
	}

	public function testDeleteTrash(): void {
		$mailboxId = 80;
		$threadRootId = 'some-thread-root-id-1';
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId($mailboxId);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId($mailAccount->getId());
		$mailbox->setName('Trash');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('find')
			->with($account, $mailbox->getName())
			->willReturn($mailbox);
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('findById')
			->with($mailbox->getId())
			->willReturn($mailbox);
		$this->threadMapper
			->expects(self::once())
			->method('findMessageUidsAndMailboxNamesByAccountAndThreadRoot')
			->with($mailAccount, $threadRootId, true)
			->willReturn([
				['messageUid' => 200, 'mailboxName' => 'Trash'],
				['messageUid' => 300, 'mailboxName' => 'Trash'],
			]);
		$this->imapClientFactory
			->expects(self::exactly(2))
			->method('getClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('expunge');
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatchTyped');

		$this->manager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
	}

	public function testDeleteThreadMessagesInDifferentMailboxes(): void {
		$threadRootId = 'some-thread-root-id-1';
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(20);
		$mailbox->setAccountId($mailAccount->getId());
		$mailbox->setName('INBOX');
		$this->threadMapper
			->expects(self::once())
			->method('findMessageUidsAndMailboxNamesByAccountAndThreadRoot')
			->with($mailAccount, $threadRootId, false)
			->willReturn([
				['messageUid' => 200, 'mailboxName' => 'INBOX'],
				['messageUid' => 300, 'mailboxName' => 'Sent'],
			]);
		$inbox = new Mailbox();
		$inbox->setId(20);
		$inbox->setName('INBOX');
		$sent = new Mailbox();
		$sent->setId(40);
		$sent->setName('Sent');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('find')
			->willReturnMap([
				[$account, 'INBOX', $inbox],
				[$account, 'Sent', $sent],
			]);
		$trash = new Mailbox();
		$trash->setId(80);
		$trash->setName('Trash');
		$this->mailboxMapper
			->expects(self::exactly(2))
			->method('findById')
			->with(80)
			->willReturn($trash);
		$this->imapClientFactory
			->expects(self::exactly(2))
			->method('getClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('move');
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatchTyped');

		$this->manager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
	}
}
