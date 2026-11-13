<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\ContextChat;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\ContextChat\ContextChatProvider;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\BackgroundJob\IJobList;
use OCP\ContextChat\Events\ContentProviderRegisterEvent;
use OCP\ContextChat\IContentManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ContextChatProviderTest extends TestCase {
	/** @var TaskService|MockObject */
	private $taskService;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IContentManager|MockObject */
	private $contentManager;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var ContextChatProvider */
	private $contextChatProvider;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(\OCP\ContextChat\IContentManager::class)) {
			$this->markTestSkipped();
		}

		$this->taskService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->contentManager = $this->createMock(IContentManager::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->contextChatProvider = new ContextChatProvider(
			$this->taskService,
			$this->accountService,
			$this->mailManager,
			$this->messageMapper,
			$this->mailboxMapper,
			$this->urlGenerator,
			$this->userManager,
			$this->contentManager,
			$this->jobList,
		);
	}

	public function provideEvents(): array {
		$mailAccount = new MailAccount();
		$mailAccount->setId(3);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$messages = [];
		$messages[] = new Message();
		$messages[0]->setId(1);
		$messages[] = new Message();
		$messages[1]->setId(2);

		if (class_exists(\OCP\ContextChat\Events\ContentProviderRegisterEvent::class)) {
			return [
				'handle ContentProviderRegisterEvent' => [new \OCP\ContextChat\Events\ContentProviderRegisterEvent($this->createMock(IContentManager::class))],
				'handle NewMessagesSynchronized' => [new NewMessagesSynchronized($account, $mailbox, $messages)],
				'handle MessageDeletedEvent' => [new MessageDeletedEvent($account, $mailbox, 1)],
			];
		}

		return [
			'handle NewMessagesSynchronized' => [new NewMessagesSynchronized($account, $mailbox, $messages)],
			'handle MessageDeletedEvent' => [new MessageDeletedEvent($account, $mailbox, 1)],
		];
	}

	/**
	 * @dataProvider provideEvents
	 */
	public function testHandleWithoutContextChat($event): void {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(false);
		$this->contentManager->expects($this->never())->method('registerContentProvider');
		$this->contentManager->expects($this->never())->method('deleteContent');
		$this->taskService->expects($this->never())->method('updateOrCreate');
		$this->contextChatProvider->handle($event);
	}

	/**
	 * @dataProvider provideEvents
	 */
	public function testHandleWithContextChat($event) {
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		if ($event instanceof ContentProviderRegisterEvent) {
			$this->contentManager->expects($this->once())
				->method('registerContentProvider');
		}

		if ($event instanceof NewMessagesSynchronized) {
			$this->taskService->expects($this->once())
				->method('updateOrCreate');
		}

		if ($event instanceof MessageDeletedEvent) {
			$this->contentManager->expects($this->once())
				->method('deleteContent');
		}

		$this->contextChatProvider->handle($event);
	}

	public function testHandleMessageDeletedUsesTheIndexedItemId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(3);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$event = new MessageDeletedEvent($account, $mailbox, 4711);
		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);
		$this->contentManager->expects($this->once())
			->method('deleteContent')
			->with('mail', $this->anything(), ['3:1:4711']);

		$this->contextChatProvider->handle($event);
	}

	public function testGetId(): void {
		$this->assertEquals('mail', $this->contextChatProvider->getId());
	}

	public function testGetAppId(): void {
		$this->assertEquals('mail', $this->contextChatProvider->getAppId());
	}

	public function testGetItemUrl(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->mailboxMapper->expects($this->once())->method('findById')->with(1)->willReturn($mailbox);
		$this->messageMapper->expects($this->once())->method('getIdForUid')->with($mailbox, 4711)->willReturn(2);
		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('mail.page.thread', ['mailboxId' => 1, 'id' => 2])
			->willReturn('http://localhost/apps/mail/box/1/thread/2');

		$itemUrl = $this->contextChatProvider->getItemUrl(ContextChatProvider::itemId(3, 1, 4711));

		$this->assertEquals('http://localhost/apps/mail/box/1/thread/2', $itemUrl);
	}

	public function testGetItemUrlNeverThrows(): void {
		$this->mailboxMapper->expects($this->once())->method('findById')
			->willThrowException(new ServiceException('database on fire'));
		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('mail.page.index', [])
			->willReturn('http://localhost/apps/mail/');

		$this->assertEquals('http://localhost/apps/mail/', $this->contextChatProvider->getItemUrl('3:1:4711'));
	}

	public function testGetItemUrlWithDeletedMessage(): void {
		$this->mailboxMapper->expects($this->once())->method('findById')->willReturn(new Mailbox());
		$this->messageMapper->expects($this->once())->method('getIdForUid')->willReturn(null);
		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('mail.page.index', [])
			->willReturn('http://localhost/apps/mail/');

		$this->assertEquals('http://localhost/apps/mail/', $this->contextChatProvider->getItemUrl('3:1:4711'));
	}

	public function testGetItemUrlWithMalformedId(): void {
		$this->mailboxMapper->expects($this->never())->method('findById');
		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('mail.page.index', [])
			->willReturn('http://localhost/apps/mail/');

		$this->assertEquals('http://localhost/apps/mail/', $this->contextChatProvider->getItemUrl('nonsense'));
	}
}
