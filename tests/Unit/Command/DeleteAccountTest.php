<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\DeleteAccount;
use OCA\Mail\Service\AccountService;
use Psr\Log\LoggerInterface;

class DeleteAccountTest extends TestCase {
	private $accountService;
	private $logger;
	private $command;
	private $args = [
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

	public function testName() {
		$this->assertSame('mail:account:delete', $this->command->getName());
	}

	public function testDescription() {
		$this->assertSame('Delete an IMAP account', $this->command->getDescription());
	}

	public function testArguments() {
		$actual = $this->command->getDefinition()->getArguments();

		foreach ($actual as $actArg) {
			$this->assertTrue($actArg->isRequired());
			$this->assertTrue(in_array($actArg->getName(), $this->args));
		}
	}
}
