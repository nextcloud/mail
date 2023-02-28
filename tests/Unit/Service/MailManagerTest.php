<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
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
			$this->threadMapper
		);
	}

	public function testGetFolders() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$folder = $this->createMock(Folder::class);
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
		$account = $this->createMock(Account::class);
		$this->eventDispatcher->expects($this->once())
			->method('dispatch')
			->with(
				$this->equalTo(BeforeMessageDeletedEvent::class),
				$this->anything()
			);
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willThrowException(new DoesNotExistException(""));
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
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->eventDispatcher->expects($this->once())
			->method('dispatch')
			->with(
				$this->equalTo(BeforeMessageDeletedEvent::class),
				$this->anything()
			);
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($this->createMock(Mailbox::class));
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(""));
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
			->method('dispatch');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($inbox);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
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
			->method('dispatch');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'Trash')
			->willReturn($source);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
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

	public function testSetCustomFlagNoIMAPCapabilities(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);

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
		$account = $this->createMock(Account::class);

		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('status')
			->willReturn([ 'permflags' => [ "11" => "\*" ] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('addFlag');

		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, true);
	}

	public function testUnsetCustomFlagWithIMAPCapabilities(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);

		$this->imapClientFactory->expects($this->any())
			->method('getClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('status')
			->willReturn([ 'permflags' => [ "11" => "\*" ] ]);
		$this->imapMessageMapper->expects($this->once())
			->method('removeFlag');

		$this->manager->flagMessage($account, 'INBOX', 123, Tag::LABEL_IMPORTANT, false);
	}

	public function testFilterFlagStandard(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$flags = [
			'seen' => [\Horde_Imap_Client::FLAG_SEEN],
			'answered' => [\Horde_Imap_Client::FLAG_ANSWERED],
			'flagged' => [\Horde_Imap_Client::FLAG_FLAGGED],
			'deleted' => [\Horde_Imap_Client::FLAG_DELETED],
			'draft' => [\Horde_Imap_Client::FLAG_DRAFT],
			'recent' => [\Horde_Imap_Client::FLAG_RECENT],
			'junk' => [\Horde_Imap_Client::FLAG_JUNK, 'junk'],
			'mdnsent' => [\Horde_Imap_Client::FLAG_MDNSENT],
		];

		//standard flags
		foreach ($flags as $k => $flag) {
			$this->assertEquals($this->manager->filterFlags($client, $account, $k, 'INBOX'), $flags[$k]);
		}
	}

	public function testSetFilterFlagsNoCapabilities() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->assertEquals([], $this->manager->filterFlags($client, $account, Tag::LABEL_IMPORTANT, 'INBOX'));
	}

	public function testSetFilterFlagsImportant() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ "11" => "\*" ]]);

		$this->assertEquals([Tag::LABEL_IMPORTANT], $this->manager->filterFlags($client, $account, Tag::LABEL_IMPORTANT, 'INBOX'));
	}

	public function testIsPermflagsEnabledTrue(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ "11" => "\*"] ]);

		$this->assertTrue($this->manager->isPermflagsEnabled($client, $account, 'INBOX'));
	}

	public function testIsPermflagsEnabledFalse(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$client->expects($this->once())
			->method('status')
			->willReturn([]);

		$this->assertFalse($this->manager->isPermflagsEnabled($client, $account, 'INBOX'));
	}

	public function testRemoveFlag(): void {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$mb = $this->createMock(Mailbox::class);
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
		$mb = $this->createMock(Mailbox::class);
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ "11" => "\*"] ]);
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
		$mb = $this->createMock(Mailbox::class);
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->with($account, 'INBOX')
			->willReturn($mb);
		$client->expects($this->once())
			->method('status')
			->willReturn(['permflags' => [ "11" => "\*"] ]);
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
		$mb = $this->createMock(Mailbox::class);
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
		$account = $this->createMock(Account::class);
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
				7
			),
		];
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
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
			->willReturnCallback(static function (Tag $tag) {
				return $tag;
			});

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
			->willReturnCallback(static function (Tag $tag) {
				return $tag;
			});

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
		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('move');
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatch');

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
		$this->imapMessageMapper
			->expects(self::exactly(2))
			->method('expunge');
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatch');

		$this->manager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
	}
}
