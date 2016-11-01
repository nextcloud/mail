<?php

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

namespace OCA\Mail\Tests\Command;

use PHPUnit_Framework_TestCase;
use OCA\Mail\Command\CreateAccount;

class CreateAccountTest extends PHPUnit_Framework_TestCase {

	private $service;
	private $crypto;
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
	];

	protected function setUp() {
		parent::setUp();

		$this->service = $this->getMockBuilder('\OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMockBuilder('\OCP\Security\ICrypto')->getMock();

		$this->command = new CreateAccount($this->service, $this->crypto);
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
			$this->assertTrue($actArg->isRequired());
			$this->assertTrue(in_array($actArg->getName(), $this->args));
		}
	}

}
