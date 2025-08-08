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
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
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

		if (!class_exists(\OCP\ContextChat\IContentManager::class)) {
			$this->markTestSkipped();
		}

		$this->taskService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->contentManager = $this->createMock(IContentManager::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->contextChatProvider = new ContextChatProvider(
			$this->taskService,
			$this->accountService,
			$this->mailManager,
			$this->messageMapper,
			$this->urlGenerator,
			$this->userManager,
			$this->contentManager,
			$this->jobList,
		);
	}

	public function provideEvents(): array {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$messages = [];
		$messages[] = new Message();
		$messages[] = new Message();

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
				->method('createOrUpdate');
		}

		if ($event instanceof MessageDeletedEvent) {
			$this->contentManager->expects($this->once())
				->method('deleteContent');
		}

		$this->contextChatProvider->handle($event);
	}

	public function testGetId(): void {
		$this->assertEquals('mail', $this->contextChatProvider->getId());
	}

	public function testGetAppId(): void {
		$this->assertEquals('mail', $this->contextChatProvider->getAppId());
	}

	public function testGetItemUrl(): void {
		$itemUrl = $this->contextChatProvider->getItemUrl('1:2');
		$this->assertEquals('http://localhost/apps/mail/box/1/thread/2', $itemUrl);
	}

	public function testTriggerInitialImport(): void {
		$user = $this->createMock(\OCP\IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$account = $this->createMock(Account::class);
		$this->accountService->expects($this->once())->method('findByUserId')->willReturn([$account]);
		$mailbox = $this->createMock(Mailbox::class);
		$mailbox->expects($this->once())->method('getId')->willReturn(1);
		$this->mailManager->expects($this->once())->method('getMailboxes')->willReturn([$mailbox]);
		$this->userManager->expects($this->once())->method('callForSeenUsers')->willReturnCallback(fn ($fn) => $fn($user));
		$this->taskService->expects($this->any())->method('createOrUpdate')->with(1, 0);
		$this->contextChatProvider->triggerInitialImport();
	}
}
