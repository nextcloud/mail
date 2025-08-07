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
use OCP\ContextChat\Events\ContentProviderRegisterEvent;
use OCP\ContextChat\IContentManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ContextChatProviderTest extends TestCase {
	/** @var TaskService|MockObject */
	private $jobsService;

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

		// TODO: setup other components if needed
		$this->jobsService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->contentManager = $this->createMock(IContentManager::class);

		$this->contextChatProvider = new ContextChatProvider(
			$this->jobsService,
			$this->accountService,
			$this->mailManager,
			$this->messageMapper,
			$this->urlGenerator,
			$this->userManager,
			$this->contentManager,
		);
	}

	public function provideEvents(): array {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$messages = [];
		$messages[] = new Message();
		$messages[] = new Message();

		return [
			// TODO: fix the following constructor
			// 'handle ContentProviderRegisterEvent' => [new ContentProviderRegisterEvent($this->contentManager)],
			'handle NewMessagesSynchronized' => [new NewMessagesSynchronized($account, $mailbox, $messages)],
			'handle MessageDeletedEvent' => [new MessageDeletedEvent($account, $mailbox, 1)],
		];
	}

	/**
	 * @dataProvider provideEvents
	 */
	public function testHandleWithoutContextChat($event) {
		// TODO: confirm test works after OCP\ContextChat classes are found
		$this->markTestIncomplete();

		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(false);
		$this->assertNull($this->contextChatProvider->handle($event));
	}

	/**
	 * @dataProvider provideEvents
	 */
	public function testHandleWithContextChat($event) {
		// TODO: confirm test works after OCP\ContextChat classes are found
		$this->markTestIncomplete();

		$this->contentManager->expects($this->once())
			->method('isContextChatAvailable')
			->willReturn(true);

		if ($event instanceof ContentProviderRegisterEvent) {
			$this->contentManager->expects($this->once())
				->method('registerContentProvider');
		}

		if ($event instanceof NewMessagesSynchronized) {
			$this->jobsService->expects($this->once())
				->method('createOrUpdate');
		}

		if ($event instanceof MessageDeletedEvent) {
			$this->contentManager->expects($this->once())
				->method('deleteContent');
		}

		$this->assertNull($this->contextChatProvider->handle($event));
	}

	public function testGetId() {
		// TODO: confirm test works after OCP\ContextChat classes are found
		$this->markTestIncomplete();

		$this->assertEquals($this->contextChatProvider->getId(), 'mail');
	}

	public function testGetAppId() {
		// TODO: confirm test works after OCP\ContextChat classes are found
		$this->markTestIncomplete();

		$this->assertEquals($this->contextChatProvider->getAppId(), 'mail');
	}

	public function testGetItemUrl() {
		// TODO: confirm test works after OCP\ContextChat classes are found
		$this->markTestIncomplete();

		$message = new Message();
		$message->setUid(2);
		$message->setMailboxId(1);
		$this->messageMapper->expects($this->once())
			->method('findByIds')
			->with('', [2], '')
			->willReturn([$message]);
		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('mail.page.thread', ['mailboxId' => 1, 'id' => '2'])
			->willReturn('http://localhost/apps/mail/box/1/thread/2');
		$itemUrl = $this->contextChatProvider->getItemUrl('2');
		$this->assertEquals($itemUrl, 'http://localhost/apps/mail/box/1/thread/2');
	}

	public function testTriggerInitialImport() {
		// TODO: finish test
		$this->markTestIncomplete();

		// $account = new Account(new MailAccount());
		// $this->accountService->expects($this->once())
		// 	->method('findByUserId')
		// 	->willReturn([$account]);
		$this->assertNull($this->contextChatProvider->triggerInitialImport());
	}
}
