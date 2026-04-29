<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\UpdateAccount;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAccountTest extends TestCase {
	private MailAccountMapper&MockObject $mapper;
	private ICrypto&MockObject $crypto;
	private UpdateAccount $command;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->command = new UpdateAccount($this->mapper, $this->crypto);
	}

	public function testName(): void {
		self::assertSame('mail:account:update-imap', $this->command->getName());
	}

	public function testAlias(): void {
		self::assertSame(['mail:account:update'], $this->command->getAliases());
	}

	public function testRejectsJmapAccount(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->with('account-id')
			->willReturn('42');
		$input->method('getOption')
			->willReturn(null);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->once())
			->method('writeln')
			->with('<error>Account 42 uses protocol jmap. Use mail:account:update-jmap instead.</error>');

		$this->mapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($mailAccount);

		self::assertSame(1, $this->command->run($input, $output));
	}

	public function testExecuteUpdatesImapAccount(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProtocol(MailAccount::PROTOCOL_IMAP);
		$mailAccount->setEmail('old@example.com');
		$mailAccount->setInboundUser('old-imap-user');

		$data = [
			'name' => 'Updated name',
			'email' => 'updated@example.com',
			'auth-method' => 'password',
			'imap-host' => 'imap.example.com',
			'imap-port' => '993',
			'imap-ssl-mode' => 'ssl',
			'imap-user' => 'imap-user',
			'imap-password' => 'imap-password',
			'smtp-host' => 'smtp.example.com',
			'smtp-port' => '465',
			'smtp-ssl-mode' => 'ssl',
			'smtp-user' => 'smtp-user',
			'smtp-password' => 'smtp-password',
		];

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->willReturnCallback(static fn (string $arg) => $arg === 'account-id' ? '42' : null);
		$input->method('getOption')
			->willReturnCallback(static fn (string $option) => $data[$option] ?? null);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->exactly(2))
			->method('writeln');
		$output->expects($this->once())
			->method('writeLn')
			->with('<info>Found account with email: old@example.com</info>');

		$this->crypto->expects($this->exactly(2))
			->method('encrypt')
			->willReturnMap([
				['imap-password', 'encrypted-imap-password'],
				['smtp-password', 'encrypted-smtp-password'],
			]);

		$this->mapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($mailAccount);
		$this->mapper->expects($this->once())
			->method('save')
			->with($this->callback(static function (MailAccount $account): bool {
				self::assertSame('Updated name', $account->getName());
				self::assertSame('updated@example.com', $account->getEmail());
				self::assertSame('password', $account->getAuthMethod());
				self::assertSame('imap.example.com', $account->getInboundHost());
				self::assertSame(993, $account->getInboundPort());
				self::assertSame('ssl', $account->getInboundSslMode());
				self::assertSame('imap-user', $account->getInboundUser());
				self::assertSame('encrypted-imap-password', $account->getInboundPassword());
				self::assertSame('smtp.example.com', $account->getOutboundHost());
				self::assertSame(465, $account->getOutboundPort());
				self::assertSame('ssl', $account->getOutboundSslMode());
				self::assertSame('smtp-user', $account->getOutboundUser());
				self::assertSame('encrypted-smtp-password', $account->getOutboundPassword());

				return true;
			}));

		self::assertSame(0, $this->command->run($input, $output));
	}
}