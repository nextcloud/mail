<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Migration\AddSieveToProvisioningConfig;
use OCA\Mail\Service\Provisioning\Config;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class AddSieveToProvisioningConfigTest extends TestCase {

	/** @var ServiceMockObject */
	private $mock;

	/** @var AddSieveToProvisioningConfig */
	private $repairStep;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(AddSieveToProvisioningConfig::class);
		$this->repairStep = $this->mock->getService();
	}


	public function testRunNoConfigToMigrate() {
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getAppValue')
			->with('mail', 'installed_version')
			->willReturn('1.8.0');

		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$output->expects($this->never())
			->method('info');

		$this->repairStep->run($output);
	}

	public function testRun() {
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getAppValue')
			->with('mail', 'installed_version')
			->willReturn('1.8.0');

		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('load')
			->willReturn(new Config([
				'email' => '%USERID%@domain.com',
				'imapUser' => '%USERID%@domain.com',
				'imapHost' => 'mx.domain.com',
				'imapPort' => 993,
				'imapSslMode' => 'ssl',
				'smtpUser' => '%USERID%@domain.com',
				'smtpHost' => 'mx.domain.com',
				'smtpPort' => 567,
				'smtpSslMode' => 'tls',
			]));

		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('save')
			->with(new Config([
				'email' => '%USERID%@domain.com',
				'imapUser' => '%USERID%@domain.com',
				'imapHost' => 'mx.domain.com',
				'imapPort' => 993,
				'imapSslMode' => 'ssl',
				'smtpUser' => '%USERID%@domain.com',
				'smtpHost' => 'mx.domain.com',
				'smtpPort' => 567,
				'smtpSslMode' => 'tls',
				'sieveEnabled' => false,
				'sieveHost' => '',
				'sievePort' => 4190,
				'sieveUser' => '',
				'sieveSslMode' => 'tls',
			]));

		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with('added sieve defaults to provisioning config');

		$this->repairStep->run($output);
	}

	public function testGetName() {
		$this->assertEquals(
			'Add sieve defaults to provisioning config',
			$this->repairStep->getName()
		);
	}
}
