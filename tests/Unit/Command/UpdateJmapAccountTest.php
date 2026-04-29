<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\UpdateJmapAccount;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateJmapAccountTest extends TestCase {
	private MailAccountMapper&MockObject $mapper;
	private ICrypto&MockObject $crypto;
	private UpdateJmapAccount $command;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->command = new UpdateJmapAccount($this->mapper, $this->crypto);
	}

	public function testName(): void {
		self::assertSame('mail:account:update-jmap', $this->command->getName());
	}

	public function testExecuteUpdatesJmapAccount(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		$mailAccount->setEmail('old@example.com');

		$data = [
			'name' => 'Updated JMAP',
			'email' => 'updated@example.com',
			'host' => 'mail.example.com',
			'port' => '443',
			'ssl-mode' => 'ssl',
			'basic-auth-user' => 'jmap-user',
			'basic-auth-password' => 'jmap-password',
			'path' => '/jmap/session',
		];

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->willReturnCallback(static fn (string $arg) => $arg === 'account-id' ? '99' : null);
		$input->method('getOption')
			->willReturnCallback(static fn (string $option) => $data[$option] ?? null);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->exactly(2))
			->method('writeln');

		$this->crypto->expects($this->once())
			->method('encrypt')
			->with('jmap-password')
			->willReturn('encrypted-jmap-password');

		$this->mapper->expects($this->once())
			->method('findById')
			->with(99)
			->willReturn($mailAccount);
		$this->mapper->expects($this->once())
			->method('save')
			->with($this->callback(static function (MailAccount $account): bool {
				self::assertSame('Updated JMAP', $account->getName());
				self::assertSame('updated@example.com', $account->getEmail());
				self::assertSame('mail.example.com', $account->getInboundHost());
				self::assertSame(443, $account->getInboundPort());
				self::assertSame('ssl', $account->getInboundSslMode());
				self::assertSame('jmap-user', $account->getInboundUser());
				self::assertSame('encrypted-jmap-password', $account->getInboundPassword());
				self::assertSame('/jmap/session', $account->getPath());

				return true;
			}));

		self::assertSame(0, $this->command->run($input, $output));
	}
}