<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\SpamReportJob;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\JunkService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JunkServiceTest extends TestCase {
	private IMailManager&MockObject $mailManager;
	private MailboxMapper&MockObject $mailboxMapper;
	private AntiSpamService&MockObject $antiSpamService;
	private IJobList&MockObject $jobList;
	private LoggerInterface&MockObject $logger;
	private JunkService $service;
	private Mailbox $inbox;
	private Mailbox $junk;

	protected function setUp(): void {
		parent::setUp();

		$this->mailManager = $this->createMock(IMailManager::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->antiSpamService = $this->createMock(AntiSpamService::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->service = new JunkService(
			$this->mailManager,
			$this->mailboxMapper,
			$this->antiSpamService,
			$this->jobList,
			$this->logger,
		);
		$this->inbox = $this->mailbox(1, 'INBOX');
		$this->junk = $this->mailbox(9, 'Junk');
		$this->mailboxMapper->method('findById')
			->willReturnCallback(fn (int $id): Mailbox => match ($id) {
				9 => $this->junk,
				default => throw new DoesNotExistException(''),
			});
		$this->mailboxMapper->method('find')
			->willReturn($this->inbox);
	}

	private function mailbox(int $id, string $name): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId($id);
		$mailbox->setName($name);
		return $mailbox;
	}

	private function account(?int $junkMailboxId): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId(3);
		$mailAccount->setJunkMailboxId($junkMailboxId);
		return new Account($mailAccount);
	}

	public function testWithoutUids(): void {
		$this->mailManager->expects($this->never())
			->method('flagMessages');

		$moved = $this->service->markMessages($this->account(9), $this->inbox, [], true);

		$this->assertFalse($moved);
	}

	public function testMarkJunkMovesToJunkAndQueuesReports(): void {
		$flags = [];
		$this->mailManager->method('flagMessages')
			->willReturnCallback(function (Account $account, Mailbox $mailbox, array $uids, string $flag, bool $value) use (&$flags): void {
				$this->assertSame([1, 2], $uids);
				$flags[] = [$flag, $value];
			});
		$this->mailManager->expects($this->once())
			->method('moveMessages')
			->with($this->anything(), $this->inbox, [1, 2], $this->junk)
			->willReturn([1 => 41, 2 => 42]);
		$this->antiSpamService->method('getSpamEmail')
			->willReturn('spam@example.com');
		$this->jobList->expects($this->once())
			->method('add')
			->with(SpamReportJob::class, [
				'accountId' => 3,
				'mailboxId' => 9,
				'uids' => [41, 42],
				'flag' => '$junk',
			]);

		$moved = $this->service->markMessages($this->account(9), $this->inbox, [1, 2], true);

		$this->assertTrue($moved);
		$this->assertSame([['$junk', true], ['$notjunk', false]], $flags);
	}

	public function testMarkJunkQueuesReportsInChunks(): void {
		$uids = range(1, 600);
		$this->mailManager->method('moveMessages')
			->willReturn(array_combine($uids, $uids));
		$this->antiSpamService->method('getSpamEmail')
			->willReturn('spam@example.com');
		$chunks = [];
		$this->jobList->expects($this->exactly(3))
			->method('add')
			->willReturnCallback(function (string $job, array $argument) use (&$chunks): void {
				$chunks[] = $argument['uids'];
			});

		$this->service->markMessages($this->account(9), $this->inbox, $uids, true);

		$this->assertSame($uids, array_merge(...$chunks));
		$this->assertCount(250, $chunks[0]);
	}

	public function testMarkJunkWithoutReporting(): void {
		$this->mailManager->method('moveMessages')
			->willReturn([1 => 41]);
		$this->antiSpamService->method('getSpamEmail')
			->willReturn('');
		$this->jobList->expects($this->never())
			->method('add');

		$moved = $this->service->markMessages($this->account(9), $this->inbox, [1], true);

		$this->assertTrue($moved);
	}

	public function testMarkJunkInsideJunkMailbox(): void {
		$this->mailManager->expects($this->never())
			->method('moveMessages');
		$this->antiSpamService->method('getSpamEmail')
			->willReturn('spam@example.com');
		$this->jobList->expects($this->once())
			->method('add')
			->with(SpamReportJob::class, $this->callback(fn (array $argument): bool => $argument['mailboxId'] === 9 && $argument['uids'] === [5]));

		$moved = $this->service->markMessages($this->account(9), $this->junk, [5], true);

		$this->assertFalse($moved);
	}

	public function testMarkJunkWithoutJunkMailbox(): void {
		$this->mailManager->expects($this->exactly(2))
			->method('flagMessages');
		$this->mailManager->expects($this->never())
			->method('moveMessages');

		$moved = $this->service->markMessages($this->account(null), $this->inbox, [1], true);

		$this->assertFalse($moved);
	}

	public function testMarkJunkWithDeletedJunkMailbox(): void {
		$this->mailManager->expects($this->never())
			->method('moveMessages');

		$moved = $this->service->markMessages($this->account(77), $this->inbox, [1], true);

		$this->assertFalse($moved);
	}

	public function testMarkNotJunkMovesToInbox(): void {
		$flags = [];
		$this->mailManager->method('flagMessages')
			->willReturnCallback(function (Account $account, Mailbox $mailbox, array $uids, string $flag, bool $value) use (&$flags): void {
				$flags[] = [$flag, $value];
			});
		$this->mailManager->expects($this->once())
			->method('moveMessages')
			->with($this->anything(), $this->junk, [5], $this->inbox)
			->willReturn([5 => 61]);
		$this->antiSpamService->method('getHamEmail')
			->willReturn('ham@example.com');
		$this->jobList->expects($this->once())
			->method('add')
			->with(SpamReportJob::class, $this->callback(fn (array $argument): bool => $argument['mailboxId'] === 1 && $argument['uids'] === [61] && $argument['flag'] === '$notjunk'));

		$moved = $this->service->markMessages($this->account(9), $this->junk, [5], false);

		$this->assertTrue($moved);
		$this->assertSame([['$junk', false], ['$notjunk', true]], $flags);
	}

	public function testMarkNotJunkInsideInbox(): void {
		$this->mailManager->expects($this->never())
			->method('moveMessages');

		$moved = $this->service->markMessages($this->account(9), $this->inbox, [1], false);

		$this->assertFalse($moved);
	}

	public function testSkipsReportsOfMessagesWithoutNewUid(): void {
		$this->mailManager->method('moveMessages')
			->willReturn([1 => 41]);
		$this->antiSpamService->method('getSpamEmail')
			->willReturn('spam@example.com');
		$this->logger->expects($this->once())
			->method('warning');
		$this->jobList->expects($this->once())
			->method('add')
			->with(SpamReportJob::class, $this->callback(fn (array $argument): bool => $argument['uids'] === [41]));

		$this->service->markMessages($this->account(9), $this->inbox, [1, 2], true);
	}
}
