<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Command\DebugAccount;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Support\DebugLogPathFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class DebugAccountTest extends TestCase {
	private AccountService|\PHPUnit\Framework\MockObject\MockObject $accountService;
	private IConfig|\PHPUnit\Framework\MockObject\MockObject $config;
	private DebugAccount $command;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->with('datadirectory')
			->willReturn('/data');
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(false);

		$this->command = new DebugAccount(
			$this->accountService,
			$this->createMock(LoggerInterface::class),
			new DebugLogPathFactory($this->config),
		);
	}

	private function mockAccount(): MailAccount {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('bob');

		$this->accountService->method('findById')
			->with(1)
			->willReturn(new Account($mailAccount));

		return $mailAccount;
	}

	public function testEnablesDebugAndPrintsLogPaths(): void {
		$mailAccount = $this->mockAccount();
		$this->accountService->expects($this->once())
			->method('save')
			->with($this->callback(fn (MailAccount $a) => $a->getDebug() === true));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '1', '--on' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('Debug mode enabled', $display);
		$this->assertStringContainsString('/data/mail-bob-1-imap.log', $display);
		$this->assertStringContainsString('/data/mail-bob-1-smtp.log', $display);
		$this->assertStringContainsString('/data/mail-bob-1-sieve.log', $display);
	}

	public function testDisablesDebugAndPrintsLogPathsWithoutDeletingThem(): void {
		$this->mockAccount();
		$this->accountService->expects($this->once())
			->method('save')
			->with($this->callback(fn (MailAccount $a) => $a->getDebug() === false));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '1', '--off' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('Debug mode disabled', $display);
		$this->assertStringContainsString('not deleted automatically', $display);
		$this->assertStringNotContainsString('still active', $display);
		$this->assertStringContainsString('/data/mail-bob-1-imap.log', $display);
		$this->assertStringContainsString('/data/mail-bob-1-smtp.log', $display);
		$this->assertStringContainsString('/data/mail-bob-1-sieve.log', $display);
	}

	public function testWarnsWhenDisablingButSystemWideDebugIsStillOn(): void {
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->with('datadirectory')
			->willReturn('/data');
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(true);
		$this->command = new DebugAccount(
			$this->accountService,
			$this->createMock(LoggerInterface::class),
			new DebugLogPathFactory($this->config),
		);
		$this->mockAccount();

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '1', '--off' => true]);

		$this->assertSame(0, $exitCode);
		$this->assertStringContainsString('still active', $tester->getDisplay());
	}

	public function testRejectsOnAndOffTogether(): void {
		$this->mockAccount();
		$this->accountService->expects($this->never())->method('save');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '1', '--on' => true, '--off' => true]);

		$this->assertSame(1, $exitCode);
	}

	public function testFailsWhenAccountDoesNotExist(): void {
		$this->accountService->method('findById')
			->with(1)
			->willThrowException(new DoesNotExistException('not found'));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['account-id' => '1']);

		$this->assertSame(1, $exitCode);
	}
}
