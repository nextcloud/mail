<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\DeleteProvisioning;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteProvisioningTest extends TestCase {
	private ProvisioningManager&MockObject $provisioningManager;
	private DeleteProvisioning $command;
	private CommandTester $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->provisioningManager = $this->createMock(ProvisioningManager::class);
		$this->command = new DeleteProvisioning($this->provisioningManager);
		$this->command->setHelperSet(new HelperSet([new QuestionHelper()]));
		$this->tester = new CommandTester($this->command);
	}

	public function testDeleteConfirmed(): void {
		$provisioning = new Provisioning();
		$provisioning->setId(3);
		$this->provisioningManager->method('getConfigById')
			->with(3)
			->willReturn($provisioning);
		$this->provisioningManager->expects(self::once())
			->method('deprovision')
			->with($provisioning);

		$this->tester->setInputs(['y']);
		$status = $this->tester->execute(['id' => '3']);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testDeleteDeclined(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn(new Provisioning());
		$this->provisioningManager->expects(self::never())
			->method('deprovision');

		$this->tester->setInputs(['n']);
		$status = $this->tester->execute(['id' => '3']);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringContainsString('Aborted', $this->tester->getDisplay());
	}

	public function testDeleteForced(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn(new Provisioning());
		$this->provisioningManager->expects(self::once())
			->method('deprovision');

		$status = $this->tester->execute(['id' => '3', '--force' => true]);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testUnknownId(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn(null);
		$this->provisioningManager->expects(self::never())
			->method('deprovision');

		$status = $this->tester->execute(['id' => '3']);

		self::assertSame(Command::FAILURE, $status);
	}

	/** @dataProvider invalidIds */
	public function testRejectsInvalidIdBeforeDeleting(string $id): void {
		$this->provisioningManager->expects(self::never())
			->method('getConfigById');
		$this->provisioningManager->expects(self::never())
			->method('deprovision');

		$status = $this->tester->execute(['id' => $id, '--force' => true]);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('positive integer', $this->tester->getDisplay());
	}

	public static function invalidIds(): array {
		return [[''], ['0'], ['-1'], ['3typo'], ['3.5'], ['3e1'], ['99999999999999999999']];
	}
}
