<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\MessageTags;
use OCA\Mail\Db\MessageTagsMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MailManagerTest extends TestCase {
	/** @var ProtocolFactory|MockObject */
	private $protocolFactory;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

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

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->dbMessageMapper = $this->createMock(DbMessageMapper::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->tagMapper = $this->createMock(TagMapper::class);
		$this->messageTagsMapper = $this->createMock(MessageTagsMapper::class);
		$this->threadMapper = $this->createMock(ThreadMapper::class);

		$this->manager = new MailManager(
			$this->mailboxMapper,
			$this->dbMessageMapper,
			$this->eventDispatcher,
			$this->logger,
			$this->tagMapper,
			$this->messageTagsMapper,
			$this->protocolFactory,
			new ImapFlag(),
			$this->threadMapper,
		);
	}

	public function testGetFolders() {
		/** @var Account|MockObject $account */
		$account = $this->createStub(Account::class);
		$mailboxes = [
			$this->createMock(Mailbox::class),
			$this->createMock(Mailbox::class),
		];
		$mailboxConnector = $this->createMock(IMailboxConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('mailboxConnector')
			->with($account)
			->willReturn($mailboxConnector);
		$mailboxConnector->expects($this->once())
			->method('syncAll')
			->with($account, false);
		$this->mailboxMapper->expects($this->once())
			->method('findAll')
			->with($this->equalTo($account))
			->willReturn($mailboxes);

		$result = $this->manager->getMailboxes($account);

		$this->assertSame($mailboxes, $result);
	}

	public function testCreateFolder() {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailboxConnector = $this->createMock(IMailboxConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('mailboxConnector')
			->with($account)
			->willReturn($mailboxConnector);
		$mailboxConnector->expects($this->once())
			->method('create')
			->with($account, 'new', [])
			->willReturn($mailbox);

		$created = $this->manager->createMailbox($account, 'new');

		$this->assertEquals($mailbox, $created);
	}

	public function testDeleteMessageTrashMailboxNotFound(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$message = new Message();
		$message->setUid(123);
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(''));
		$this->expectException(ServiceException::class);

		$this->manager->deleteMessage(
			$account,
			$mailbox,
			$message
		);
	}

	public function testDeleteMessage(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$inbox = new Mailbox();
		$inbox->setId(1);
		$inbox->setName('INBOX');
		$trash = new Mailbox();
		$trash->setId(123);
		$trash->setName('Trash');
		$message = new Message();
		$message->setUid(123);
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('moveMessages')
			->with($account, $trash, $inbox, $message)
			->willReturn([$message]);
		$this->dbMessageMapper->expects($this->once())
			->method('updateBulk');

		$this->manager->deleteMessage(
			$account,
			$inbox,
			$message
		);
	}

	public function testExpungeMessage(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$source = new Mailbox();
		$source->setId(123);
		$source->setName('Trash');
		$trash = new Mailbox();
		$trash->setId(123);
		$trash->setName('Trash');
		$message = new Message();
		$message->setUid(123);
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('deleteMessages')
			->with($account, $source, $message)
			->willReturn([$message]);
		$this->dbMessageMapper->expects($this->once())
			->method('deleteByUid')
			->with($source, 123);

		$this->manager->deleteMessage(
			$account,
			$source,
			$message
		);
	}

	public function testFlagMessages(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$message = new Message();
		$message->setUid(123);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('flagMessages')
			->with($account, $mailbox, 'seen', true, $message)
			->willReturn([$message]);
		$this->dbMessageMapper->expects($this->once())
			->method('updateBulk')
			->with($account, false, $message);
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped');

		$this->manager->flagMessages($account, $mailbox, 'seen', true, $message);
	}

	public function testMarkFolderAsReadDoesNotUpdateTags(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(123);
		$this->dbMessageMapper->method('findAllUids')
			->with($mailbox)
			->willReturn([123]);
		$this->dbMessageMapper->method('findByUids')
			->with($mailbox, [123])
			->willReturn([$message]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->method('messageConnector')->willReturn($messageConnector);
		$messageConnector->expects(self::once())
			->method('flagMessages')
			->with($account, $mailbox, 'seen', true, $message)
			->willReturn([$message]);
		$this->dbMessageMapper->expects(self::once())
			->method('updateBulk')
			->with($account, false, $message);

		$this->manager->markFolderAsRead($account, $mailbox);
	}

	public function testIsPermflagsEnabled(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox)
			->willReturn(true);

		$this->assertTrue($this->manager->isPermflagsEnabled($account, $mailbox));
	}

	public function testTagMessage(): void {
		$account = $this->createStub(Account::class);
		$account->method('getUserId')->willReturn('user');
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new Message();
		$message->setUid(123);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$otherTag = new Tag();
		$otherTag->setImapLabel('$custom');
		$this->tagMapper->expects($this->once())
			->method('getAllTagsForMessages')
			->with([$message], 'user')
			->willReturn([$message->getMessageId() => [$otherTag]]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, true, $message)
			->willReturnCallback(function (Account $a, Mailbox $m, Tag $t, bool $value, Message $message) use ($otherTag, $tag): array {
				$this->assertSame([$otherTag], $message->getTags());
				$message->setTags([$otherTag, $tag]);
				return [$message];
			});
		$this->dbMessageMapper->expects($this->once())
			->method('updateBulk')
			->with($account, true, $this->callback(function (Message $message) use ($otherTag, $tag): bool {
				return $message->getTags() === [$otherTag, $tag];
			}));

		$this->manager->tagMessages($account, $mailbox, $tag, true, $message);
	}

	public function testUntagMessage(): void {
		$account = $this->createStub(Account::class);
		$account->method('getUserId')->willReturn('user');
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new Message();
		$message->setUid(123);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$otherTag = new Tag();
		$otherTag->setImapLabel('$custom');
		$this->tagMapper->expects($this->once())
			->method('getAllTagsForMessages')
			->with([$message], 'user')
			->willReturn([$message->getMessageId() => [$otherTag, $tag]]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, false, $message)
			->willReturnCallback(function (Account $a, Mailbox $m, Tag $t, bool $value, Message $message) use ($otherTag, $tag): array {
				$this->assertSame([$otherTag, $tag], $message->getTags());
				$message->setTags([$otherTag]);
				return [$message];
			});
		$this->dbMessageMapper->expects($this->once())
			->method('updateBulk')
			->with($account, true, $this->callback(function (Message $message) use ($otherTag, $tag): bool {
				return $message->getTags() === [$otherTag];
			}));

		$this->manager->tagMessages($account, $mailbox, $tag, false, $message);
	}

	public function testTagMessagesWithoutExistingTags(): void {
		$account = $this->createStub(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$tag = new Tag();
		$message = new Message();
		$message->setMessageId('<message@example.com>');
		$messageWithoutId = new Message();
		$this->tagMapper->expects(self::once())
			->method('getAllTagsForMessages')
			->with([$message, $messageWithoutId], 'user')
			->willReturn([]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->method('messageConnector')->willReturn($messageConnector);
		$messageConnector->expects(self::once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, true, $message, $messageWithoutId)
			->willReturnCallback(static function (Account $a, Mailbox $m, Tag $t, bool $value, Message ...$messages): array {
				foreach ($messages as $message) {
					self::assertSame([], $message->getTags());
					$message->setTags([$t]);
				}
				return $messages;
			});
		$this->dbMessageMapper->expects(self::once())
			->method('updateBulk')
			->with($account, true, $message, $messageWithoutId);

		$this->manager->tagMessages($account, $mailbox, $tag, true, $message, $messageWithoutId);
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
		$account = $this->createStub(Account::class);
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
		$mailbox = new Mailbox();
		$mailbox->setName('Inbox');
		$message = new Message();
		$message->setUid(123);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects($this->once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects($this->once())
			->method('fetchAttachments')
			->with($account, $mailbox, $message)
			->willReturn($attachments);

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
		$dstMailbox = new Mailbox();
		$dstMailbox->setId($dstMailboxId);
		$dstMailbox->setAccountId($mailAccount->getId());
		$dstMailbox->setName('Trash');
		$message1 = new Message();
		$message1->setUid(200);
		$message2 = new Message();
		$message2->setUid(300);
		$this->mailboxMapper
			->expects(self::once())
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
		$this->dbMessageMapper
			->expects(self::once())
			->method('findByUids')
			->with($srcMailbox, [200, 300])
			->willReturn([$message1, $message2]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory
			->expects(self::once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector
			->expects(self::once())
			->method('moveMessages')
			->with($account, $dstMailbox, $srcMailbox, $message1, $message2)
			->willReturn([$message1, $message2]);
		$this->dbMessageMapper
			->expects(self::once())
			->method('updateBulk');

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
		$dstMailbox = new Mailbox();
		$dstMailbox->setId($dstMailboxId);
		$dstMailbox->setAccountId($mailAccount->getId());
		$dstMailbox->setName('INBOX');
		$message1 = new Message();
		$message1->setUid(200);
		$message2 = new Message();
		$message2->setUid(300);
		$this->mailboxMapper
			->expects(self::once())
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
		$this->dbMessageMapper
			->expects(self::once())
			->method('findByUids')
			->with($srcMailbox, [200, 300])
			->willReturn([$message1, $message2]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory
			->expects(self::once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector
			->expects(self::once())
			->method('moveMessages')
			->with($account, $dstMailbox, $srcMailbox, $message1, $message2)
			->willReturn([$message1, $message2]);
		$this->dbMessageMapper
			->expects(self::once())
			->method('updateBulk');

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
		$trashMailbox = new Mailbox();
		$trashMailbox->setId($trashMailboxId);
		$trashMailbox->setAccountId($mailAccount->getId());
		$trashMailbox->setName('Trash');
		$message1 = new Message();
		$message1->setUid(200);
		$message2 = new Message();
		$message2->setUid(300);
		$this->mailboxMapper
			->expects(self::once())
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
		$this->dbMessageMapper
			->expects(self::once())
			->method('findByUids')
			->with($mailbox, [200, 300])
			->willReturn([$message1, $message2]);
		$this->mailboxMapper
			->expects(self::once())
			->method('findById')
			->with($trashMailbox->getId())
			->willReturn($trashMailbox);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory
			->expects(self::once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector
			->expects(self::once())
			->method('moveMessages')
			->with($account, $trashMailbox, $mailbox, $message1, $message2)
			->willReturn([$message1, $message2]);
		$this->dbMessageMapper
			->expects(self::once())
			->method('updateBulk');
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
		$message1 = new Message();
		$message1->setUid(200);
		$message2 = new Message();
		$message2->setUid(300);
		$this->mailboxMapper
			->expects(self::once())
			->method('find')
			->with($account, $mailbox->getName())
			->willReturn($mailbox);
		$this->mailboxMapper
			->expects(self::once())
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
		$this->dbMessageMapper
			->expects(self::once())
			->method('findByUids')
			->with($mailbox, [200, 300])
			->willReturn([$message1, $message2]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory
			->expects(self::once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector
			->expects(self::once())
			->method('deleteMessages')
			->with($account, $mailbox, $message1, $message2)
			->willReturn([$message1, $message2]);
		$this->dbMessageMapper
			->expects(self::once())
			->method('deleteByUid')
			->with($mailbox, 200, 300);
		$this->eventDispatcher
			->expects(self::exactly(4))
			->method('dispatchTyped');

		$this->manager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
	}

	public function testClearMailboxWithoutMessagesDoesNothing(): void {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->dbMessageMapper->expects(self::once())
			->method('findAllUids')
			->with($mailbox)
			->willReturn([]);
		$this->dbMessageMapper->expects(self::never())
			->method('findByUids');
		$this->protocolFactory->expects(self::never())
			->method('messageConnector');

		$this->manager->clearMailbox($account, $mailbox);
	}

	public function testClearMailboxMovesAllMessagesToTrash(): void {
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$inbox = new Mailbox();
		$inbox->setId(1);
		$inbox->setName('INBOX');
		$trash = new Mailbox();
		$trash->setId(123);
		$trash->setName('Trash');
		$message1 = new Message();
		$message1->setUid(11);
		$message2 = new Message();
		$message2->setUid(12);
		$this->dbMessageMapper->expects(self::once())
			->method('findAllUids')
			->with($inbox)
			->willReturn([11, 12]);
		$this->dbMessageMapper->expects(self::once())
			->method('findByUids')
			->with($inbox, [11, 12])
			->willReturn([$message1, $message2]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($trash);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->expects(self::once())
			->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		$messageConnector->expects(self::once())
			->method('moveMessages')
			->with($account, $trash, $inbox, $message1, $message2)
			->willReturn([$message1, $message2]);
		$this->dbMessageMapper->expects(self::once())
			->method('updateBulk');

		$this->manager->clearMailbox($account, $inbox);
	}

	public function testDeleteTagUntagsMessagesGroupedByMailbox(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$tag = new Tag();
		$tag->setImapLabel('$label1');
		$this->tagMapper->expects(self::once())
			->method('getTagForUser')
			->with(5, 'user')
			->willReturn($tag);
		$messageTag = new MessageTags();
		$messageTag->setImapMessageId('<msg@id>');
		$this->messageTagsMapper->expects(self::once())
			->method('getMessagesByTag')
			->with(5)
			->willReturn([$messageTag]);
		// two messages of the same tag living in different mailboxes
		$messageInInbox = new Message();
		$messageInInbox->setUid(11);
		$messageInInbox->setMessageId('<msg@id>');
		$messageInInbox->setMailboxId(1);
		$messageInArchive = new Message();
		$messageInArchive->setUid(22);
		$messageInArchive->setMessageId('<msg@id>');
		$messageInArchive->setMailboxId(2);
		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->with($account, '<msg@id>')
			->willReturn([$messageInInbox, $messageInArchive]);
		$otherTag = new Tag();
		$otherTag->setImapLabel('$custom');
		$this->tagMapper->expects(self::exactly(2))
			->method('getAllTagsForMessages')
			->with($this->callback(static fn (array $messages): bool => count($messages) === 1), 'user')
			->willReturn(['<msg@id>' => [$tag, $otherTag]]);
		$this->dbMessageMapper->expects(self::exactly(2))
			->method('updateBulk')
			->with($account, true, $this->callback(static fn (Message $message): bool => $message->getTags() === [$otherTag]));
		$inbox = new Mailbox();
		$inbox->setId(1);
		$archive = new Mailbox();
		$archive->setId(2);
		$this->mailboxMapper->method('findById')
			->willReturnMap([
				[1, $inbox],
				[2, $archive],
			]);
		$messageConnector = $this->createMock(IMessageConnector::class);
		$this->protocolFactory->method('messageConnector')
			->with($account)
			->willReturn($messageConnector);
		// the connector is asked to untag once per mailbox, with that mailbox's messages
		$tagCalls = [];
		$messageConnector->expects(self::exactly(2))
			->method('tagMessages')
			->willReturnCallback(function (Account $a, Mailbox $mailbox, Tag $t, bool $value, Message ...$messages) use (&$tagCalls, $tag, $otherTag): array {
				self::assertFalse($value);
				self::assertSame($tag, $t);
				foreach ($messages as $message) {
					self::assertSame([$tag, $otherTag], $message->getTags());
					$message->setTags([$otherTag]);
				}
				$tagCalls[$mailbox->getId()] = $messages;
				return $messages;
			});
		$this->messageTagsMapper->expects(self::once())
			->method('delete')
			->with($messageTag);
		$this->tagMapper->expects(self::once())
			->method('delete')
			->with($tag)
			->willReturn($tag);

		$this->manager->deleteTag(5, 'user', [$account]);

		self::assertEquals([$messageInInbox], $tagCalls[1]);
		self::assertEquals([$messageInArchive], $tagCalls[2]);
	}
}
