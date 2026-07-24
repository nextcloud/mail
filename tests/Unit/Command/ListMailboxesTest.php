<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Command\ListMailboxes;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ListMailboxesTest extends TestCase {
	private AccountService|\PHPUnit\Framework\MockObject\MockObject $accountService;
	private MailboxMapper|\PHPUnit\Framework\MockObject\MockObject $mailboxMapper;
	private ITimeFactory|\PHPUnit\Framework\MockObject\MockObject $timeFactory;
	private ListMailboxes $command;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$this->command = new ListMailboxes(
			$this->accountService,
			$this->mailboxMapper,
			$this->timeFactory,
		);
	}

	private function mockAccount(): Account {
		$account = new Account($this->createMock(MailAccount::class));

		$this->accountService->method('findById')
			->with(42)
			->willReturn($account);

		return $account;
	}

	public function testName(): void {
		$this->assertSame('mail:mailbox:list', $this->command->getName());
	}

	public function testListsMailboxes(): void {
		$account = $this->mockAccount();
		$inbox = new Mailbox();
		$inbox->setId(1);
		$inbox->setName('INBOX');
		$inbox->setSpecialUse('["inbox"]');
		$inbox->setMessages(5);
		$inbox->setUnseen(2);
		$inbox->setShared(false);
		$inbox->setSyncNewLock(999);
		$inbox->setSyncVanishedLock(999);

		$this->mailboxMapper->method('findAll')
			->with($account)
			->willReturn([$inbox]);

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '42']);

		$this->assertSame(Command::SUCCESS, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('INBOX', $display);
		$this->assertStringNotContainsString('Sync in background', $display);
		$this->assertStringContainsString('Shared', $display);
		$this->assertStringContainsString('Lock', $display);
	}

	public function testShowsNoLockWhenUnlocked(): void {
		$account = $this->mockAccount();
		$inbox = new Mailbox();
		$inbox->setId(1);
		$inbox->setName('INBOX');
		$inbox->setSpecialUse('["inbox"]');
		$inbox->setMessages(5);
		$inbox->setUnseen(2);

		$this->mailboxMapper->method('findAll')
			->with($account)
			->willReturn([$inbox]);

		$tester = new CommandTester($this->command);
		$tester->execute(['account-id' => '42']);

		$this->assertStringContainsString('-', $tester->getDisplay());
	}

	public function testOrdersMailboxesByNameThenId(): void {
		$account = $this->mockAccount();
		$work2 = new Mailbox();
		$work2->setId(2);
		$work2->setName('Work');
		$work2->setSpecialUse('work-mailbox-two');
		$sent = new Mailbox();
		$sent->setId(3);
		$sent->setName('Sent');
		$sent->setSpecialUse('sent-mailbox');
		$work1 = new Mailbox();
		$work1->setId(1);
		$work1->setName('Work');
		$work1->setSpecialUse('work-mailbox-one');

		$this->mailboxMapper->method('findAll')
			->with($account)
			->willReturn([$work2, $sent, $work1]);

		$tester = new CommandTester($this->command);
		$tester->execute(['account-id' => '42']);

		$display = $tester->getDisplay();
		$sentPos = strpos($display, 'sent-mailbox');
		$work1Pos = strpos($display, 'work-mailbox-one');
		$work2Pos = strpos($display, 'work-mailbox-two');
		$this->assertLessThan($work1Pos, $sentPos);
		$this->assertLessThan($work2Pos, $work1Pos);
	}

	public function testBoldsTheInboxName(): void {
		$account = $this->mockAccount();
		$inbox = new Mailbox();
		$inbox->setId(1);
		$inbox->setName('INBOX');
		$inbox->setSpecialUse('["inbox"]');
		$sent = new Mailbox();
		$sent->setId(2);
		$sent->setName('Sent');
		$sent->setSpecialUse('["sent"]');

		$this->mailboxMapper->method('findAll')
			->with($account)
			->willReturn([$inbox, $sent]);

		$tester = new CommandTester($this->command);
		$tester->execute(['account-id' => '42'], ['decorated' => true]);

		$display = $tester->getDisplay();
		$this->assertStringContainsString("\033[1mINBOX\033[22m", $display);
		$this->assertStringNotContainsString("\033[1mSent\033[22m", $display);
	}

	public function testFailsWhenAccountDoesNotExist(): void {
		$this->accountService->method('findById')
			->with(42)
			->willThrowException(new DoesNotExistException('not found'));
		$this->mailboxMapper->expects($this->never())
			->method('findAll');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '42']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}
}
