<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\SpamReportJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AntiSpamService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SpamReportJobTest extends TestCase {
	private AccountService&MockObject $accountService;
	private MailboxMapper&MockObject $mailboxMapper;
	private AntiSpamService&MockObject $antiSpamService;
	private LoggerInterface&MockObject $logger;
	private SpamReportJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->antiSpamService = $this->createMock(AntiSpamService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->job = new SpamReportJob(
			$this->createMock(ITimeFactory::class),
			$this->accountService,
			$this->mailboxMapper,
			$this->antiSpamService,
			$this->logger,
		);
		$this->job->setArgument([
			'accountId' => 3,
			'mailboxId' => 9,
			'uids' => [41, 42],
			'flag' => '$junk',
		]);
	}

	public function testSendsAReportPerMessage(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$this->accountService->method('findById')
			->with(3)
			->willReturn($account);
		$this->mailboxMapper->method('findById')
			->with(9)
			->willReturn($mailbox);
		$reported = [];
		$this->antiSpamService->expects($this->exactly(2))
			->method('sendReportEmail')
			->willReturnCallback(function (Account $a, Mailbox $mb, int $uid, string $flag) use (&$reported, $account, $mailbox): void {
				$this->assertSame($account, $a);
				$this->assertSame($mailbox, $mb);
				$this->assertSame('$junk', $flag);
				$reported[] = $uid;
			});

		$this->job->start($this->createMock(IJobList::class));

		$this->assertSame([41, 42], $reported);
	}

	public function testContinuesAfterAFailedReport(): void {
		$this->accountService->method('findById')
			->willReturn(new Account(new MailAccount()));
		$this->mailboxMapper->method('findById')
			->willReturn(new Mailbox());
		$this->antiSpamService->expects($this->exactly(2))
			->method('sendReportEmail')
			->willReturnCallback(function (Account $a, Mailbox $mb, int $uid): void {
				if ($uid === 41) {
					throw new ServiceException('SMTP down');
				}
			});
		$this->logger->expects($this->once())
			->method('error');

		$this->job->start($this->createMock(IJobList::class));
	}

	public function testSkipsDeletedMailbox(): void {
		$this->accountService->method('findById')
			->willReturn(new Account(new MailAccount()));
		$this->mailboxMapper->method('findById')
			->willThrowException(new DoesNotExistException(''));
		$this->antiSpamService->expects($this->never())
			->method('sendReportEmail');

		$this->job->start($this->createMock(IJobList::class));
	}
}
