<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\CreateImapAccount;
use OCA\Mail\Command\StdinImapPasswords;
use OCA\Mail\Command\UpdateImapAccount;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class StdinImapCommandTest extends TestCase {
	private function streamInput(array $args, array $options, string $contents): StreamableInputInterface {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $contents);
		rewind($stream);
		$input = $this->createMock(StreamableInputInterface::class);
		$input->method('getStream')->willReturn($stream);
		$input->method('getArgument')->willReturnCallback(static fn (string $name) => $args[$name] ?? null);
		$input->method('getOption')->willReturnCallback(static fn (string $name) => $options[$name] ?? null);
		return $input;
	}

	public function testCreateReadsBothPasswordsAndUsesNativeEncryption(): void {
		$service = $this->createMock(AccountService::class);
		$crypto = $this->createMock(ICrypto::class);
		$users = $this->createMock(IUserManager::class);
		$classification = $this->createMock(ClassificationSettingsService::class);
		$users->method('userExists')->with('test-user')->willReturn(true);
		$crypto->expects(self::exactly(2))->method('encrypt')->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$service->expects(self::once())->method('save')->with(self::callback(static function (MailAccount $account): bool {
			self::assertSame('encrypted:imap-synthetic', $account->getInboundPassword());
			self::assertSame('encrypted:smtp-synthetic', $account->getOutboundPassword());
			return true;
		}))->willReturnCallback(static fn (MailAccount $account) => $account);
		$input = $this->streamInput([
			'user-id' => 'test-user', 'name' => 'Synthetic', 'email' => 'test@example.org',
			'imap-host' => 'imap.example.org', 'imap-port' => '993', 'imap-ssl-mode' => 'ssl', 'imap-user' => 'test@example.org', 'imap-password' => '-',
			'smtp-host' => 'smtp.example.org', 'smtp-port' => '465', 'smtp-ssl-mode' => 'ssl', 'smtp-user' => 'test@example.org', 'smtp-password' => '-',
			'auth-method' => 'password',
		], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true], "imap-synthetic\nsmtp-synthetic\n");
		self::assertSame(0, (new CreateImapAccount($service, $crypto, $users, $classification))->run($input, new BufferedOutput()));
	}

	public function testUpdateReadsBothPasswordsAndUsesNativeEncryption(): void {
		$mapper = $this->createMock(MailAccountMapper::class);
		$crypto = $this->createMock(ICrypto::class);
		$account = new MailAccount();
		$account->setProtocol(MailAccount::PROTOCOL_IMAP);
		$account->setEmail('test@example.org');
		$mapper->method('findById')->with(42)->willReturn($account);
		$crypto->expects(self::exactly(2))->method('encrypt')->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$mapper->expects(self::once())->method('save')->with(self::callback(static function (MailAccount $account): bool {
			self::assertSame('encrypted:imap-synthetic', $account->getInboundPassword());
			self::assertSame('encrypted:smtp-synthetic', $account->getOutboundPassword());
			return true;
		}));
		$input = $this->streamInput(['account-id' => '42'], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true], "imap-synthetic\nsmtp-synthetic\n");
		self::assertSame(0, (new UpdateImapAccount($mapper, $crypto))->run($input, new BufferedOutput()));
	}

	public function testCreateRejectsPasswordArgumentsInStdinMode(): void {
		$service = $this->createMock(AccountService::class);
		$crypto = $this->createMock(ICrypto::class);
		$users = $this->createMock(IUserManager::class);
		$classification = $this->createMock(ClassificationSettingsService::class);
		$service->expects(self::never())->method('save');
		$input = $this->streamInput(['imap-password' => 'sensitive-in-argv', 'smtp-password' => '-'], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true], "imap\nsmtp\n");
		$output = new BufferedOutput();
		self::assertSame(1, (new CreateImapAccount($service, $crypto, $users, $classification))->run($input, $output));
		self::assertStringNotContainsString('sensitive-in-argv', $output->fetch());
	}


	public function testCreateRejectsIncompleteStreamWithoutSavingOrLoggingSecrets(): void {
		$service = $this->createMock(AccountService::class);
		$crypto = $this->createMock(ICrypto::class);
		$users = $this->createMock(IUserManager::class);
		$classification = $this->createMock(ClassificationSettingsService::class);
		$service->expects(self::never())->method('save');
		$input = $this->streamInput(['imap-password' => '-', 'smtp-password' => '-'], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true], "synthetic-imap\n");
		$output = new BufferedOutput();

		self::assertSame(1, (new CreateImapAccount($service, $crypto, $users, $classification))->run($input, $output));
		self::assertStringNotContainsString('synthetic-imap', $output->fetch());
	}

	public function testUpdateRejectsIncompleteStreamWithoutSavingOrLoggingSecrets(): void {
		$mapper = $this->createMock(MailAccountMapper::class);
		$crypto = $this->createMock(ICrypto::class);
		$mapper->expects(self::never())->method('save');
		$input = $this->streamInput(['account-id' => '42'], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true], "imap-synthetic\n");
		$output = new BufferedOutput();
		self::assertSame(1, (new UpdateImapAccount($mapper, $crypto))->run($input, $output));
		self::assertStringNotContainsString('imap-synthetic', $output->fetch());
	}

	public function testUpdateRejectsMixingStdinWithLegacyPasswordOptions(): void {
		$mapper = $this->createMock(MailAccountMapper::class);
		$crypto = $this->createMock(ICrypto::class);
		$mapper->expects(self::never())->method('save');
		$input = $this->streamInput(['account-id' => '42'], [StdinImapPasswords::OPTION_PASSWORDS_FROM_STDIN => true, 'imap-password' => 'legacy-sensitive'], "imap\nsmtp\n");
		$output = new BufferedOutput();
		self::assertSame(1, (new UpdateImapAccount($mapper, $crypto))->run($input, $output));
		self::assertStringNotContainsString('legacy-sensitive', $output->fetch());
	}


	public function testCreateParsesRealConsoleArguments(): void {
		$service = $this->createMock(AccountService::class);
		$crypto = $this->createMock(ICrypto::class);
		$users = $this->createMock(IUserManager::class);
		$classification = $this->createMock(ClassificationSettingsService::class);
		$users->method('userExists')->willReturn(true);
		$crypto->expects(self::exactly(2))->method('encrypt')
			->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$service->expects(self::once())->method('save')
			->with(self::callback(static function (MailAccount $account): bool {
				self::assertSame('encrypted:imap-console', $account->getInboundPassword());
				self::assertSame('encrypted:smtp-console', $account->getOutboundPassword());
				return true;
			}))->willReturnCallback(static fn (MailAccount $account) => $account);
		$command = new CreateImapAccount($service, $crypto, $users, $classification);
		$input = new StringInput('test-user Synthetic test@example.org imap.example.org 993 ssl test@example.org - smtp.example.org 465 ssl test@example.org - --passwords-from-stdin');
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, "imap-console\nsmtp-console\n");
		rewind($stream);
		$input->setStream($stream);
		$output = new BufferedOutput();

		self::assertSame(0, $command->run($input, $output));
		self::assertStringNotContainsString('imap-console', $output->fetch());
	}

	public function testUpdateParsesRealConsoleOptions(): void {
		$mapper = $this->createMock(MailAccountMapper::class);
		$crypto = $this->createMock(ICrypto::class);
		$account = new MailAccount();
		$account->setProtocol(MailAccount::PROTOCOL_IMAP);
		$account->setEmail('test@example.org');
		$mapper->method('findById')->with(42)->willReturn($account);
		$crypto->expects(self::exactly(2))->method('encrypt')
			->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$mapper->expects(self::once())->method('save')
			->with(self::callback(static function (MailAccount $saved): bool {
				self::assertSame('encrypted:imap-console', $saved->getInboundPassword());
				self::assertSame('encrypted:smtp-console', $saved->getOutboundPassword());
				return true;
			}));
		$command = new UpdateImapAccount($mapper, $crypto);
		$input = new StringInput('42 --passwords-from-stdin');
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, "imap-console\nsmtp-console\n");
		rewind($stream);
		$input->setStream($stream);
		$output = new BufferedOutput();

		self::assertSame(0, $command->run($input, $output));
		self::assertStringNotContainsString('smtp-console', $output->fetch());
	}

	public function testLegacyCreateStillAcceptsPositionalPasswords(): void {
		$service = $this->createMock(AccountService::class);
		$crypto = $this->createMock(ICrypto::class);
		$users = $this->createMock(IUserManager::class);
		$classification = $this->createMock(ClassificationSettingsService::class);
		$users->method('userExists')->willReturn(true);
		$crypto->expects(self::exactly(2))->method('encrypt')->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$service->expects(self::once())->method('save')->with(self::callback(static function (MailAccount $account): bool {
			self::assertSame('encrypted:legacy-imap', $account->getInboundPassword());
			self::assertSame('encrypted:legacy-smtp', $account->getOutboundPassword());
			return true;
		}))->willReturnCallback(static fn (MailAccount $account) => $account);
		$input = $this->streamInput([
			'user-id' => 'test-user', 'name' => 'Synthetic', 'email' => 'test@example.org',
			'imap-host' => 'imap.example.org', 'imap-port' => '993', 'imap-ssl-mode' => 'ssl', 'imap-user' => 'test@example.org', 'imap-password' => 'legacy-imap',
			'smtp-host' => 'smtp.example.org', 'smtp-port' => '465', 'smtp-ssl-mode' => 'ssl', 'smtp-user' => 'test@example.org', 'smtp-password' => 'legacy-smtp',
			'auth-method' => 'password',
		], [], '');
		self::assertSame(0, (new CreateImapAccount($service, $crypto, $users, $classification))->run($input, new BufferedOutput()));
	}

	public function testLegacyUpdateStillAcceptsPasswordOptions(): void {
		$mapper = $this->createMock(MailAccountMapper::class);
		$crypto = $this->createMock(ICrypto::class);
		$account = new MailAccount();
		$account->setProtocol(MailAccount::PROTOCOL_IMAP);
		$account->setEmail('test@example.org');
		$mapper->method('findById')->willReturn($account);
		$crypto->expects(self::exactly(2))->method('encrypt')->willReturnCallback(static fn (string $value) => 'encrypted:' . $value);
		$mapper->expects(self::once())->method('save')->with(self::callback(static function (MailAccount $account): bool {
			self::assertSame('encrypted:legacy-imap', $account->getInboundPassword());
			self::assertSame('encrypted:legacy-smtp', $account->getOutboundPassword());
			return true;
		}));
		$input = $this->streamInput(['account-id' => '42'], ['imap-password' => 'legacy-imap', 'smtp-password' => 'legacy-smtp'], '');
		self::assertSame(0, (new UpdateImapAccount($mapper, $crypto))->run($input, new BufferedOutput()));
	}
}
