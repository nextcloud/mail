<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
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
		$this->crypto = $this->getMock('\OCP\Security\ICrypto');

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
