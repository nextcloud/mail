<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\UnlockMailbox;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UnlockMailboxTest extends TestCase {
	private MailboxMapper|\PHPUnit\Framework\MockObject\MockObject $mailboxMapper;
	private ITimeFactory|\PHPUnit\Framework\MockObject\MockObject $timeFactory;
	private UnlockMailbox $command;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$this->command = new UnlockMailbox(
			$this->mailboxMapper,
			$this->timeFactory,
		);
	}

	public function testName(): void {
		$this->assertSame('mail:mailbox:unlock', $this->command->getName());
	}

	public function testUnlocksAMailboxWithOnlyStaleLocks(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);
		$mailbox->setSyncNewLock(1);

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromNewSync')->with($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromChangedSync')->with($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromVanishedSync')->with($mailbox);

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::SUCCESS, $exitCode);
	}

	public function testReportsNotLockedForAnAlreadyUnlockedMailbox(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->mailboxMapper->expects($this->never())->method('unlockFromNewSync');
		$this->mailboxMapper->expects($this->never())->method('unlockFromChangedSync');
		$this->mailboxMapper->expects($this->never())->method('unlockFromVanishedSync');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::SUCCESS, $exitCode);
		$this->assertStringContainsString('Mailbox not locked', $tester->getDisplay());
	}

	public function testRefusesToUnlockAnActivelyLockedMailbox(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);
		$mailbox->setSyncNewLock(999);

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->mailboxMapper->expects($this->never())->method('unlockFromNewSync');
		$this->mailboxMapper->expects($this->never())->method('unlockFromChangedSync');
		$this->mailboxMapper->expects($this->never())->method('unlockFromVanishedSync');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}

	public function testForceUnlocksAnActivelyLockedMailbox(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);
		$mailbox->setSyncNewLock(999);

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromNewSync')->with($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromChangedSync')->with($mailbox);
		$this->mailboxMapper->expects($this->once())->method('unlockFromVanishedSync')->with($mailbox);

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10', '--force' => true]);

		$this->assertSame(Command::SUCCESS, $exitCode);
	}

	public function testFailsWhenMailboxDoesNotExist(): void {
		$this->mailboxMapper->method('findById')
			->with(10)
			->willThrowException(new DoesNotExistException('not found'));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}
}
