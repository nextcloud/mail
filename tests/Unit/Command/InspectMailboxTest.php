<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Cache\HordeSyncTokenParser;
use OCA\Mail\Command\InspectMailbox;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InspectMailboxTest extends TestCase {
	private MailboxMapper|\PHPUnit\Framework\MockObject\MockObject $mailboxMapper;
	private ITimeFactory|\PHPUnit\Framework\MockObject\MockObject $timeFactory;
	private InspectMailbox $command;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$this->command = new InspectMailbox(
			$this->mailboxMapper,
			$this->timeFactory,
			new HordeSyncTokenParser(),
		);
	}

	public function testName(): void {
		$this->assertSame('mail:mailbox:info', $this->command->getName());
	}

	public function testShowsMailboxDetails(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(2);
		$mailbox->setName('INBOX');
		$mailbox->setSpecialUse('["inbox"]');
		$mailbox->setMessages(5);
		$mailbox->setUnseen(2);
		$mailbox->setSyncInBackground(true);
		$mailbox->setShared(false);
		$mailbox->setSyncNewLock(999);
		$mailbox->setSyncNewToken(base64_encode('U100,V200,H300'));
		$mailbox->setAttributes('["\\\\HasNoChildren"]');
		$mailbox->setDelimiter('.');
		$mailbox->setSelectable(true);

		$this->mailboxMapper->method('findById')
			->with(1)
			->willReturn($mailbox);

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '1']);

		$this->assertSame(Command::SUCCESS, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('INBOX', $display);
		$this->assertStringNotContainsString('Name hash', $display);
		$this->assertStringContainsString('Delimiter', $display);
		$this->assertStringContainsString('Selectable', $display);
		$this->assertStringContainsString('Lock New', $display);
		$this->assertStringContainsString(date('Y-m-d H:i:s', 999) . ' (active)', $display);
		$this->assertStringContainsString('Lock Changed', $display);
		$this->assertStringContainsString('Token New', $display);
		$this->assertStringContainsString('uid=100, validity=200, modseq=300', $display);
	}

	public function testShowsStaleLock(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(2);
		$mailbox->setName('INBOX');
		$mailbox->setSyncNewLock(1);

		$this->mailboxMapper->method('findById')
			->with(1)
			->willReturn($mailbox);

		$tester = new CommandTester($this->command);
		$tester->execute(['mailbox-id' => '1']);

		$this->assertStringContainsString(date('Y-m-d H:i:s', 1) . ' (stale)', $tester->getDisplay());
	}

	public function testShowsSyncInBackgroundYesForInboxRegardlessOfFlag(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(2);
		$mailbox->setName('INBOX');
		$mailbox->setSyncInBackground(false);

		$this->mailboxMapper->method('findById')
			->with(1)
			->willReturn($mailbox);

		$tester = new CommandTester($this->command);
		$tester->execute(['mailbox-id' => '1']);

		$display = $tester->getDisplay();
		$this->assertMatchesRegularExpression('/Sync In Background\s*\|\s*yes/', $display);
	}

	public function testShowsSyncInBackgroundDashForNonInboxWithoutFlag(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setAccountId(2);
		$mailbox->setName('Archive');

		$this->mailboxMapper->method('findById')
			->with(1)
			->willReturn($mailbox);

		$tester = new CommandTester($this->command);
		$tester->execute(['mailbox-id' => '1']);

		$display = $tester->getDisplay();
		$this->assertMatchesRegularExpression('/Sync In Background\s*\|\s*-/', $display);
	}

	public function testFailsWhenMailboxDoesNotExist(): void {
		$this->mailboxMapper->method('findById')
			->with(1)
			->willThrowException(new DoesNotExistException('not found'));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '1']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}
}
