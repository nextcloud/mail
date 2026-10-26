<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob\ContextChat;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\ContextChat\ScheduleJob;
use OCA\Mail\Db\ContextChat\Task;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\ContextChatSettingsService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\ContextChat\IContentManager;
use OCP\DB\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ScheduleJobTest extends TestCase {
	private const ARGUMENT = ['accountId' => 42];

	private ITimeFactory&MockObject $time;
	private TaskService&MockObject $taskService;
	private AccountService&MockObject $accountService;
	private MailManager&MockObject $mailManager;
	private LoggerInterface&MockObject $logger;
	private IJobList&MockObject $jobList;
	private ContextChatSettingsService&MockObject $settingsService;
	private IContentManager&MockObject $contentManager;
	private ScheduleJob $job;
	private Account $account;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(IContentManager::class)) {
			$this->markTestSkipped();
		}

		$this->time = $this->createMock(ITimeFactory::class);
		$this->taskService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->settingsService = $this->createMock(ContextChatSettingsService::class);
		$this->contentManager = $this->createMock(IContentManager::class);

		$this->time->method('getTime')->willReturn(2 * 24 * 60 * 60);
		$this->contentManager->method('isContextChatAvailable')->willReturn(true);
		$mailAccount = new MailAccount();
		$mailAccount->setId(self::ARGUMENT['accountId']);
		$mailAccount->setUserId('user');
		$this->account = new Account($mailAccount);

		$this->job = new ScheduleJob(
			$this->time,
			$this->taskService,
			$this->accountService,
			$this->mailManager,
			$this->logger,
			$this->jobList,
			$this->settingsService,
			$this->contentManager,
		);
		$this->job->setArgument(self::ARGUMENT);
		$this->job->setLastRun(0);
	}

	private function createMailbox(int $id, bool $cached): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId($id);
		if ($cached) {
			$mailbox->setSyncNewToken('new');
			$mailbox->setSyncChangedToken('changed');
			$mailbox->setSyncVanishedToken('vanished');
		}
		return $mailbox;
	}

	public function testResetsCachedMailboxesAndRemovesItself(): void {
		$this->accountService->method('findById')->willReturn($this->account);
		$this->settingsService->method('isIndexingEnabled')->with('user')->willReturn(true);
		$this->mailManager->method('getMailboxes')->willReturn([
			$this->createMailbox(1, true),
			$this->createMailbox(2, false),
			$this->createMailbox(3, true),
		]);
		$scheduled = [];
		$this->taskService->expects($this->exactly(2))
			->method('updateOrCreate')
			->willReturnCallback(function (int $mailboxId, int $lastMessageId) use (&$scheduled) {
				$scheduled[$mailboxId] = $lastMessageId;
				return new Task();
			});
		$this->jobList->expects($this->once())
			->method('remove')
			->with(ScheduleJob::class, self::ARGUMENT);

		$this->job->start($this->jobList);

		$this->assertSame([1 => 0, 3 => 0], $scheduled);
	}

	public function testRemovesItselfWhenSchedulingATaskFails(): void {
		$this->accountService->method('findById')->willReturn($this->account);
		$this->settingsService->method('isIndexingEnabled')->willReturn(true);
		$this->mailManager->method('getMailboxes')->willReturn([$this->createMailbox(1, true)]);
		$this->taskService->method('updateOrCreate')->willThrowException(new Exception());
		$this->logger->expects($this->once())->method('warning');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(ScheduleJob::class, self::ARGUMENT);

		$this->job->start($this->jobList);
	}

	public function testPostponesUntilInitialSync(): void {
		$this->accountService->method('findById')->willReturn($this->account);
		$this->settingsService->method('isIndexingEnabled')->willReturn(true);
		$this->mailManager->method('getMailboxes')->willReturn([$this->createMailbox(1, false)]);
		$this->taskService->expects($this->never())->method('updateOrCreate');
		$this->jobList->expects($this->never())->method('remove');

		$this->job->start($this->jobList);
	}

	public function testPostponesWhileIndexingIsDisabled(): void {
		$this->accountService->method('findById')->willReturn($this->account);
		$this->settingsService->method('isIndexingEnabled')->willReturn(false);
		$this->mailManager->expects($this->never())->method('getMailboxes');
		$this->taskService->expects($this->never())->method('updateOrCreate');
		$this->jobList->expects($this->never())->method('remove');

		$this->job->start($this->jobList);
	}

	public function testRemovesItselfForDeletedAccount(): void {
		$this->accountService->method('findById')->willThrowException(new DoesNotExistException(''));
		$this->taskService->expects($this->never())->method('updateOrCreate');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(ScheduleJob::class, self::ARGUMENT);

		$this->job->start($this->jobList);
	}
}
