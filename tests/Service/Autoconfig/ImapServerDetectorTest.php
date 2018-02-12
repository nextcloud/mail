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
use OCA\Mail\Service\AutoConfig\ImapConnectivityTester;
use OCA\Mail\Service\AutoConfig\ImapServerDetector;
use OCA\Mail\Service\AutoConfig\MxRecord;
use PHPUnit_Framework_MockObject_MockObject;

class ImapServerDetectorTest extends TestCase {

	/** @var MxRecord|PHPUnit_Framework_MockObject_MockObject */
	private $mxRecord;

	/** @var ImapConnectivityTester|PHPUnit_Framework_MockObject_MockObject */
	private $imapConnectivityTester;

	/** @var ImapServerDetector */
	private $detector;

	protected function setUp() {
		parent::setUp();

		$this->mxRecord = $this->createMock(MxRecord::class);
		$this->imapConnectivityTester = $this->createMock(ImapConnectivityTester::class);

		$this->detector = new ImapServerDetector($this->mxRecord, $this->imapConnectivityTester);
	}

	public function testDetectNo() {
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$name = 'User';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(['mx.domain.tld']);
		$this->imapConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($email), $this->equalTo('mx.domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn($this->createMock(MailAccount::class));

		$account = $this->detector->detect($email, $password, $name);

		$this->assertNotNull($account);
		$this->assertInstanceOf(MailAccount::class, $account);
	}

	public function testDetectNoMxRecordsFound() {
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$name = 'User';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(false);
		$this->imapConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($email), $this->equalTo('domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn($this->createMock(MailAccount::class));

		$account = $this->detector->detect($email, $password, $name);

		$this->assertNotNull($account);
		$this->assertInstanceOf(MailAccount::class, $account);
	}

	public function testDetectNoMxRecordsFoundAndFallbackFails() {
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$name = 'User';
		$this->mxRecord->expects($this->once())
			->method('query')
			->with($this->equalTo('domain.tld'))
			->willReturn(false);
		$this->imapConnectivityTester->expects($this->once())
			->method('test')
			->with($this->equalTo($email), $this->equalTo('domain.tld'), $this->equalTo(['user', 'user@domain.tld']))
			->willReturn(null);

		$account = $this->detector->detect($email, $password, $name);

		$this->assertNull($account);
	}

}
