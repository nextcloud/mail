<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\ContextChat;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\ContextChat\SubmitContentJob;
use OCA\Mail\ContextChat\ContextChatProvider;
use OCA\Mail\Db\ContextChat\Task;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\ContextChat\IContentManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SubmitContentJobTest extends TestCase {
	/** @var TaskService|MockObject */
	private $taskService;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IContentManager|MockObject */
	private $contentManager;

	/** @var ContextChatProvider */
	private $contextChatProvider;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(\OCP\ContextChat\IContentManager::class)) {
			$this->markTestSkipped();
		}

		$this->time = $this->createMock(ITimeFactory::class);
		$this->taskService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->contextChatProvider = $this->createMock(ContextChatProvider::class);
		$this->contentManager = $this->createMock(IContentManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);

		$this->submitContentJob = new SubmitContentJob(
			$this->time,
			$this->taskService,
			$this->accountService,
			$this->mailManager,
			$this->messageMapper,
			$this->imapClientFactory,
			$this->contextChatProvider,
			$this->contentManager,
			$this->logger,
			$this->mailboxMapper,
		);
	}

	public function provideEvents(): array {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$messages = [];
		$messages[] = new Message();
		$messages[] = new Message();

		return [
			'handle ContentProviderRegisterEvent' => [new \OCP\ContextChat\Events\ContentProviderRegisterEvent($this->createMock(IContentManager::class))],
			'handle NewMessagesSynchronized' => [new NewMessagesSynchronized($account, $mailbox, $messages)],
			'handle MessageDeletedEvent' => [new MessageDeletedEvent($account, $mailbox, 1)],
		];
	}


	public function testRunWithoutContextChat(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(false);
		$this->time->expects($this->any())->method('getTime')
			->willReturn(12 * 60 * 60);
		$this->taskService->expects($this->never())->method('findNext');
		$this->mailboxMapper->expects($this->never())->method('findById');
		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChat(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);
		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willReturn($mailbox);
		$this->time->expects($this->any())->method('getTime')
			->willReturn(
				// returned when Job#start asks
				12 * 60 * 60,
				12 * 60 * 60,
				// returned when filtering messages
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				// returned before processing messages
				0,
				// returned on first message
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
			);
		$this->messageMapper->expects($this->once())->method('findIdsAfter')
			->with($mailbox, 0, 0, ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS)->willReturn([2]);
		$account = $this->createMock(Account::class);
		$account->expects($this->any())->method('getUserId')->willReturn('user123');
		$this->accountService->expects($this->once())->method('findById')->willReturn($account);
		$message = new Message();
		$message->setId(2);
		$message->setUid(2);
		$this->messageMapper->expects($this->once())->method('findByIds')->willReturn([$message]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())->method('getClient')->willReturn($client);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects($this->once())->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->expects($this->once())->method('isEncrypted')->willReturn(false);
		$imapMessage->expects($this->once())->method('getUid')->willReturn(12);
		$imapMessage->expects($this->once())->method('getFullMessage')->willReturn(['body' => 'full message']);
		$imapMessage->expects($this->once())->method('getSubject')->willReturn('subject');
		$sent = new \Horde_Imap_Client_DateTime('2025-01-01 00:00:00');
		$imapMessage->expects($this->once())->method('getSentDate')->willReturn($sent);
		$client->expects($this->once())->method('close');
		$this->contextChatProvider->expects($this->once())->method('getAppId')->willReturn('mail');
		$this->contextChatProvider->expects($this->once())->method('getId')->willReturn('mail');
		$this->contentManager->expects($this->once())->method('submitContent');
		$this->taskService->expects($this->once())->method('updateOrCreate')->with($task->getMailboxId(), 2);

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithNoMessagesToProcess(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);
		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willReturn($mailbox);
		$this->time->expects($this->any())->method('getTime')
			->willReturn(
				// returned when Job#start asks
				12 * 60 * 60,
				12 * 60 * 60,
				// returned when filtering messages
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE
			);
		$this->messageMapper->expects($this->once())->method('findIdsAfter')
			->with($mailbox, 0, 0, ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS)->willReturn([]);
		$this->taskService->expects($this->once())->method('delete')->with($task->getId());
		$this->messageMapper->expects($this->never())->method('findByIds');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithTimeout(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);
		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willReturn($mailbox);
		$this->time->expects($this->any())->method('getTime')
			->willReturn(
				// returned when Job#start asks
				12 * 60 * 60,
				12 * 60 * 60,
				// returned when filtering messages
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				// returned before processing messages
				0,
				// returned on first message -- will prevent message from being processed
				ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL + 100,
				ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL + 100,
				ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL + 100,
				ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL + 100,
				ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL + 100
			);
		$this->messageMapper->expects($this->once())->method('findIdsAfter')
			->with($mailbox, 0, 0, ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS)->willReturn([1]);
		$account = $this->createMock(Account::class);
		$account->expects($this->any())->method('getUserId')->willReturn('user123');
		$this->accountService->expects($this->once())->method('findById')->with()->willReturn($account);
		$message = new Message();
		$this->messageMapper->expects($this->once())->method('findByIds')->willReturn([$message]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())->method('getClient')->willReturn($client);
		$this->mailManager->expects($this->never())->method('getImapMessage'); // will not get called because the job takes too long already
		$client->expects($this->once())->method('close');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithEncryptedMessage(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);
		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willReturn($mailbox);
		$this->time->expects($this->any())->method('getTime')
			->willReturn(
				// returned when Job#start asks
				12 * 60 * 60,
				12 * 60 * 60,
				// returned when filtering messages
				ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE,
				// returned before processing messages
				0,
				// returned on first message
				0,
				0,
				0,
				0,
			);
		$this->messageMapper->expects($this->once())->method('findIdsAfter')
			->with($mailbox, 0, 0, ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS)->willReturn([1]);
		$account = $this->createMock(Account::class);
		$account->expects($this->any())->method('getUserId')->willReturn('user123');
		$this->accountService->expects($this->once())->method('findById')->with()->willReturn($account);
		$message = new Message();
		$message->setId(1);
		$message->setUid(1);
		$this->messageMapper->expects($this->once())->method('findByIds')->willReturn([$message]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())->method('getClient')->willReturn($client);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects($this->once())->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->expects($this->once())->method('isEncrypted')->willReturn(true);
		$imapMessage->expects($this->never())->method('getFullMessage');
		$client->expects($this->once())->method('close');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithFindNextTaskException(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		$this->time->expects($this->any())->method('getTime')->willReturn(60 * 60 * 12);

		$this->taskService->expects($this->once())->method('findNext')->willThrowException(new \OCP\DB\Exception('An error'));
		$this->contentManager->expects($this->never())->method('submitContent');
		$this->imapClientFactory->expects($this->never())->method('getClient');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithFindNextTaskException2(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		$this->time->expects($this->any())->method('getTime')->willReturn(60 * 60 * 12);

		$this->taskService->expects($this->once())->method('findNext')->willThrowException(new DoesNotExistException('ERROR'));
		$this->contentManager->expects($this->never())->method('submitContent');
		$this->imapClientFactory->expects($this->never())->method('getClient');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithFindByIdException1(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		$this->time->expects($this->any())->method('getTime')->willReturn(60 * 60 * 12);

		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willThrowException(new \OCA\Mail\Exception\ServiceException());
		$this->contentManager->expects($this->never())->method('submitContent');
		$this->imapClientFactory->expects($this->never())->method('getClient');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}

	public function testRunWithContextChatWithFindByIdException2(): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		$this->time->expects($this->any())->method('getTime')->willReturn(60 * 60 * 12);

		$task = new Task();
		$task->setLastMessageId(0);
		$task->setMailboxId(1);
		$task->setId(1);
		$this->taskService->expects($this->once())->method('findNext')->willReturn($task);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(5);
		$this->mailboxMapper->expects($this->once())->method('findById')->willThrowException(new DoesNotExistException('ERROR'));
		$this->contentManager->expects($this->never())->method('submitContent');
		$this->imapClientFactory->expects($this->never())->method('getClient');

		$this->submitContentJob->setLastRun(0);
		$this->submitContentJob->start($this->createMock(IJobList::class));
	}
}
