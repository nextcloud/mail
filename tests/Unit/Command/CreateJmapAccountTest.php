<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\CreateJmapAccount;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateJmapAccountTest extends TestCase {
	private AccountService&MockObject $service;
	private ICrypto&MockObject $crypto;
	private IUserManager&MockObject $userManager;
	private ClassificationSettingsService&MockObject $classificationSettingsService;
	private CreateJmapAccount $command;
	private array $args = [
		'user-id',
		'name',
		'email',
		'host',
		'port',
		'ssl-mode',
		'basic-auth-user',
		'basic-auth-password',
		'path',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->getMockBuilder(AccountService::class)
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->createMock(ICrypto::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->classificationSettingsService = $this->createMock(ClassificationSettingsService::class);

		$this->command = new CreateJmapAccount(
			$this->service,
			$this->crypto,
			$this->userManager,
			$this->classificationSettingsService,
		);
	}

	public function testName(): void {
		$this->assertSame('mail:account:create-jmap', $this->command->getName());
	}

	public function testDescription(): void {
		$this->assertSame('creates a JMAP mail account', $this->command->getDescription());
	}

	public function testArguments(): void {
		$actual = $this->command->getDefinition()->getArguments();

		foreach ($actual as $actArg) {
			if ($actArg->getName() === 'path') {
				self::assertFalse($actArg->isRequired());
			} else {
				self::assertTrue($actArg->isRequired());
			}
			self::assertTrue(in_array($actArg->getName(), $this->args, true));
		}
	}

	public function testInvalidUserId(): void {
		$userId = 'invalidUser';
		$data = [
			'user-id' => $userId,
			'name' => '',
			'email' => '',
			'host' => '',
			'port' => 0,
			'ssl-mode' => '',
			'basic-auth-user' => '',
			'basic-auth-password' => '',
			'path' => null,
		];

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->willReturnCallback(fn (string $arg) => $data[$arg] ?? null);
		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->once())
			->method('writeln')
			->with("<error>User $userId does not exist</error>");

		$this->userManager->expects($this->once())
			->method('userExists')
			->with($userId)
			->willReturn(false);

		$this->assertEquals(1, $this->command->run($input, $output));
	}

	public function testExecuteCreatesJmapAccount(): void {
		$data = [
			'user-id' => 'user-id',
			'name' => 'Personal',
			'email' => 'user@example.com',
			'host' => 'mail.example.com',
			'port' => '443',
			'ssl-mode' => 'ssl',
			'basic-auth-user' => 'jmap-user',
			'basic-auth-password' => 'jmap-password',
			'path' => '/.well-known/jmap',
		];

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->willReturnCallback(fn (string $arg) => $data[$arg] ?? null);
		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->once())
			->method('writeln')
			->with('<info>JMAP account 42 for user@example.com created</info>');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('user-id')
			->willReturn(true);

		$this->crypto->expects($this->once())
			->method('encrypt')
			->with('jmap-password')
			->willReturn('encrypted-password');

		$this->classificationSettingsService->expects($this->once())
			->method('isClassificationEnabledByDefault')
			->willReturn(true);

		$this->service->expects($this->once())
			->method('save')
			->willReturnCallback(function (MailAccount $account): MailAccount {
				self::assertSame('user-id', $account->getUserId());
				self::assertSame('Personal', $account->getName());
				self::assertSame('user@example.com', $account->getEmail());
				self::assertSame(MailAccount::PROTOCOL_JMAP, $account->getProtocol());
				self::assertSame('mail.example.com', $account->getInboundHost());
				self::assertSame(443, $account->getInboundPort());
				self::assertSame('ssl', $account->getInboundSslMode());
				self::assertSame('jmap-user', $account->getInboundUser());
				self::assertSame('encrypted-password', $account->getInboundPassword());
				self::assertSame('/.well-known/jmap', $account->getPath());
				self::assertTrue($account->getClassificationEnabled());

				$account->setId(42);
				return $account;
			});

		$this->assertEquals(0, $this->command->run($input, $output));
	}
}
