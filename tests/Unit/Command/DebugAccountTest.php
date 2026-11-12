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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class DebugAccountTest extends TestCase {
	private AccountService&MockObject $accountService;
	private string $dataDirectory;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->dataDirectory = sys_get_temp_dir() . '/' . uniqid('mail-debug-', true);
		mkdir($this->dataDirectory);
	}

	protected function tearDown(): void {
		array_map('unlink', glob($this->dataDirectory . '/*'));
		rmdir($this->dataDirectory);

		parent::tearDown();
	}

	private function createCommand(bool $systemDebug): DebugAccount {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')
			->with('datadirectory')
			->willReturn($this->dataDirectory);
		$config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn($systemDebug);

		return new DebugAccount(
			$this->accountService,
			$this->createMock(LoggerInterface::class),
			new DebugLogPathFactory($config),
		);
	}

	private function mockAccount(bool $debug = false): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('bob');
		$mailAccount->setDebug($debug);

		$this->accountService->method('findById')
			->with(1)
			->willReturn(new Account($mailAccount));
	}

	public function testEnablesDebugAndPrintsLogPaths(): void {
		$this->mockAccount();
		$this->accountService->expects($this->once())
			->method('save')
			->with($this->callback(fn (MailAccount $a) => $a->getDebug() === true));
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1', '--on' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('Debug mode enabled', $display);
		$this->assertStringContainsString($this->dataDirectory . '/mail-bob-1-imap.log', $display);
		$this->assertStringContainsString($this->dataDirectory . '/mail-bob-1-smtp.log', $display);
		$this->assertStringContainsString($this->dataDirectory . '/mail-bob-1-sieve.log', $display);
		$this->assertStringNotContainsString('not deleted automatically', $display);
	}

	public function testDisablesDebugAndPrintsExistingLogFiles(): void {
		$this->mockAccount(true);
		touch($this->dataDirectory . '/mail-bob-1-imap.log');
		$this->accountService->expects($this->once())
			->method('save')
			->with($this->callback(fn (MailAccount $a) => $a->getDebug() === false));
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1', '--off' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('Debug mode disabled', $display);
		$this->assertStringNotContainsString('still active', $display);
		$this->assertStringContainsString($this->dataDirectory . '/mail-bob-1-imap.log', $display);
		$this->assertStringNotContainsString('smtp.log', $display);
		$this->assertStringNotContainsString('sieve.log', $display);
		$this->assertStringContainsString('not deleted automatically', $display);
	}

	public function testDisablesDebugWithoutLogFiles(): void {
		$this->mockAccount(true);
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1', '--off' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('Debug mode disabled', $display);
		$this->assertStringNotContainsString('Log files', $display);
	}

	public function testWarnsWhenDisablingButSystemWideDebugIsStillOn(): void {
		$this->mockAccount(true);
		$tester = new CommandTester($this->createCommand(true));

		$exitCode = $tester->execute(['account-id' => '1', '--off' => true]);

		$this->assertSame(0, $exitCode);
		$display = $tester->getDisplay();
		$this->assertStringContainsString('still active', $display);
		$this->assertStringContainsString($this->dataDirectory . '/mail-bob-1-imap.log', $display);
		$this->assertStringNotContainsString('not deleted automatically', $display);
	}

	public function testShowsStatusWithoutChangingIt(): void {
		$this->mockAccount(true);
		$this->accountService->expects($this->never())->method('save');
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1']);

		$this->assertSame(0, $exitCode);
		$this->assertStringContainsString('Debug mode enabled', $tester->getDisplay());
	}

	public function testRejectsOnAndOffTogether(): void {
		$this->mockAccount();
		$this->accountService->expects($this->never())->method('save');
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1', '--on' => true, '--off' => true]);

		$this->assertSame(1, $exitCode);
	}

	public function testFailsWhenAccountDoesNotExist(): void {
		$this->accountService->method('findById')
			->with(1)
			->willThrowException(new DoesNotExistException('not found'));
		$tester = new CommandTester($this->createCommand(false));

		$exitCode = $tester->execute(['account-id' => '1']);

		$this->assertSame(1, $exitCode);
	}
}
