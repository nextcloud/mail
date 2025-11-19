<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\DeleteAccount;
use OCA\Mail\Service\AccountService;
use Psr\Log\LoggerInterface;

class DeleteAccountTest extends TestCase {
	private $accountService;
	private $logger;
	private ?\OCA\Mail\Command\DeleteAccount $command = null;
	private array $args = [
		'account-id',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->getMockBuilder(AccountService::class)
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->command = new DeleteAccount($this->accountService, $this->logger);
	}

	public function testName(): void {
		$this->assertSame('mail:account:delete', $this->command->getName());
	}

	public function testDescription(): void {
		$this->assertSame('Delete an IMAP account', $this->command->getDescription());
	}

	public function testArguments(): void {
		$actual = $this->command->getDefinition()->getArguments();

		foreach ($actual as $actArg) {
			$this->assertTrue($actArg->isRequired());
			$this->assertTrue(in_array($actArg->getName(), $this->args));
		}
	}
}
