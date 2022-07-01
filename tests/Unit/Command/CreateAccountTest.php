<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCA\Mail\Command\CreateAccount;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAccountTest extends TestCase {
	private $service;
	private $crypto;
	private $userManager;
	private $command;
	private $args = [
		'user-id',
		'name',
		'email',
		'imap-host',
		'imap-port',
		'imap-ssl-mode',
		'imap-user',
		'imap-password',
		'smtp-host',
		'smtp-port',
		'smtp-ssl-mode',
		'smtp-user',
		'smtp-password',
		'auth-method',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->getMockBuilder('\OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMockBuilder('\OCP\Security\ICrypto')->getMock();
		$this->userManager = $this->createMock(IUserManager::class);

		$this->command = new CreateAccount($this->service, $this->crypto, $this->userManager);
	}

	public function testName() {
		$this->assertSame('mail:account:create', $this->command->getName());
	}

	public function testDescription() {
		$this->assertSame('creates IMAP account', $this->command->getDescription());
	}

	public function testArguments() {
		$actual = $this->command->getDefinition()->getArguments();

		foreach ($actual as $actArg) {
			if ($actArg->getName() === 'auth-method') {
				self::assertFalse($actArg->isRequired());
			} else {
				self::assertTrue($actArg->isRequired());
			}
			self::assertTrue(in_array($actArg->getName(), $this->args));
		}
	}

	public function testInvalidUserId() {
		$userId = 'invalidUser';
		$data = [
			'user-id' => $userId,
			'name' => '',
			'email' => '',
			'imap-host' => '',
			'imap-port' => 0,
			'imap-ssl-mode' => '',
			'imap-user' => '',
			'imap-password' => '',
			'smtp-host' => '',
			'smtp-port' => 0,
			'smtp-ssl-mode' => '',
			'smtp-user' => '',
			'smtp-password' => '',
		];

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->willReturnCallback(function ($arg) use ($data) {
				return $data[$arg] ?? null;
			});
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
}
