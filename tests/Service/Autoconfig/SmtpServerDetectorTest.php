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

namespace OCA\Mail\Tests\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\MxRecord;
use OCA\Mail\Service\AutoConfig\SmtpConnectivityTester;
use OCA\Mail\Service\AutoConfig\SmtpServerDetector;
use PHPUnit_Framework_MockObject_MockObject;

class SmtpServerDetectorTest extends TestCase {

	/** @var MxRecord|PHPUnit_Framework_MockObject_MockObject */
	private $mxRecord;

	/** @var SmtpConnectivityTester|PHPUnit_Framework_MockObject_MockObject */
	private $smtpConnectivityTester;

	/** @var SmtpServerDetector */
	private $detector;

	protected function setUp() {
		parent::setUp();

		$this->mxRecord = $this->createMock(MxRecord::class);
		$this->smtpConnectivityTester = $this->createMock(SmtpConnectivityTester::class);

		$this->detector = new SmtpServerDetector($this->mxRecord, $this->smtpConnectivityTester, true);
	}

	public function testDetectNo() {
		$account = $this->createMock(MailAccount::class);
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(['mx.domain.tld']);
		$this->smtpConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($account), $this->equalTo('mx.domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn($this->createMock(MailAccount::class));

		$result = $this->detector->detect($account, $email, $password);

		$this->assertTrue($result);
	}

	public function testDetectNoMxRecordsFound() {
		$account = $this->createMock(MailAccount::class);
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(false);
		$this->smtpConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($account), $this->equalTo('domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn(true);

		$result = $this->detector->detect($account, $email, $password);

		$this->assertTrue($result);
	}

	public function testDetectNoMxRecordsFoundAndFallbackFails() {
		$account = $this->createMock(MailAccount::class);
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(false);
		$this->smtpConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($account), $this->equalTo('domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn(false);

		$result = $this->detector->detect($account, $email, $password);

		$this->assertFalse($result);
	}

}
