<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Service\Autoconfig;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\ConfigurationDetector;
use OCA\Mail\Service\AutoConfig\ImapServerDetector;
use OCA\Mail\Service\AutoConfig\SmtpServerDetector;
use OCA\Mail\Tests\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class ConfigurationDetectorTest extends TestCase {

	/** @var ImapServerDetector|PHPUnit_Framework_MockObject_MockObject */
	private $imapServerDetector;

	/** @var SmtpServerDetector|PHPUnit_Framework_MockObject_MockObject */
	private $smtpServerDetector;

	/** @var ConfigurationDetector */
	private $detector;

	protected function setUp() {
		parent::setUp();

		$this->imapServerDetector = $this->createMock(ImapServerDetector::class);
		$this->smtpServerDetector = $this->createMock(SmtpServerDetector::class);

		$this->detector = new ConfigurationDetector($this->imapServerDetector, $this->smtpServerDetector);
	}

	public function testFailingImapDetection() {
		$this->imapServerDetector->expects($this->once())
			->method('detect')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn(null);
		$this->smtpServerDetector->expects($this->never())
			->method('detect');

		$result = $this->detector->detectImapAndSmtp('user@domain.tld', 'mypassword', 'User');

		$this->assertNull($result);
	}

	public function testDetection() {
		$account = $this->createMock(MailAccount::class);
		$this->imapServerDetector->expects($this->once())
			->method('detect')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn($account);
		$this->smtpServerDetector->expects($this->once())
			->method('detect')
			->with($account, 'user@domain.tld', 'mypassword');

		$result = $this->detector->detectImapAndSmtp('user@domain.tld', 'mypassword', 'User');

		$this->assertNotNull($result);
	}

}
