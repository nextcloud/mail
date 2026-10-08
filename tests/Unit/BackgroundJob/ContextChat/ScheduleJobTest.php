<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob\ContextChat;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\ContextChat\ScheduleJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\ContextChatSettingsService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\ContextChat\IContentManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ScheduleJobTest extends TestCase {
	private TaskService&MockObject $taskService;
	private AccountService&MockObject $accountService;
	private MailManager&MockObject $mailManager;
	private ContextChatSettingsService&MockObject $contextChatSettingsService;
	private MockObject $contentManager;
	private ProtocolFactory&MockObject $protocolFactory;
	private ScheduleJob $job;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(IContentManager::class)) {
			$this->markTestSkipped();
		}

		$time = $this->createMock(ITimeFactory::class);
		$time->method('getTime')
			->willReturn(10_000_000);
		$this->taskService = $this->createMock(TaskService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->contextChatSettingsService = $this->createMock(ContextChatSettingsService::class);
		$this->contentManager = $this->createMock(IContentManager::class);
		$this->protocolFactory = $this->createMock(ProtocolFactory::class);

		$this->job = new ScheduleJob(
			$time,
			$this->taskService,
			$this->accountService,
			$this->mailManager,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IJobList::class),
			$this->contextChatSettingsService,
			$this->contentManager,
			$this->protocolFactory,
		);
		$this->job->setArgument([
			'accountId' => 123,
		]);
	}

	private function mockIndexableAccount(): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setUserId('user123');
		$account = new Account($mailAccount);
		$this->contentManager->method('isContextChatAvailable')
			->willReturn(true);
		$this->accountService->method('findById')
			->with(123)
			->willReturn($account);
		$this->contextChatSettingsService->method('isIndexingEnabled')
			->willReturn(true);
		return $account;
	}

	public function testReleasesClientsAfterLoadingMailboxes(): void {
		$account = $this->mockIndexableAccount();
		$mailbox = new Mailbox();
		$mailbox->setId(7);
		$this->mailManager->method('getMailboxes')
			->with($account)
			->willReturn([$mailbox]);
		$this->taskService->expects(self::once())
			->method('updateOrCreate')
			->with(7, 0);
		$this->protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);

		$this->job->start($this->createMock(IJobList::class));
	}

	public function testReleasesClientsWhenLoadingMailboxesFails(): void {
		$account = $this->mockIndexableAccount();
		$this->mailManager->method('getMailboxes')
			->willThrowException(new ServiceException('Could not sync mailboxes'));
		$this->taskService->expects(self::never())
			->method('updateOrCreate');
		$this->protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);

		$this->job->start($this->createMock(IJobList::class));
	}

	public function testDoesNotReleaseWhenContextChatIsUnavailable(): void {
		$this->contentManager->method('isContextChatAvailable')
			->willReturn(false);
		$this->mailManager->expects(self::never())
			->method('getMailboxes');
		$this->protocolFactory->expects(self::never())
			->method('releaseClients');

		$this->job->start($this->createMock(IJobList::class));
	}
}
