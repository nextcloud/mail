<?php
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Tests\Unit\Service\Provisioning;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Provisioning\Config;
use OCA\Mail\Service\Provisioning\Manager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

class ManagerTest extends TestCase {

	/** @var ServiceMockObject */
	private $mock;

	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(Manager::class);
		$this->manager = $this->mock->getService();
	}

	public function testProvision() {
		$config = new TestConfig();
		$this->mock->getParameter('userManager')
			->expects($this->once())
			->method('callForAllUsers');

		$cnt = $this->manager->provision($config);

		$this->assertEquals(0, $cnt);
	}

	public function testUpdateProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$config = new TestConfig();
		$account = $this->createMock(MailAccount::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($account);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($account);

		$this->manager->provisionSingleUser($config, $user);
	}

	public function testProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$config = new TestConfig();
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willThrowException($this->createMock(DoesNotExistException::class));
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('insert');

		$this->manager->provisionSingleUser($config, $user);
	}

	public function testGetNoConfig() {
		$config = $this->manager->getConfig();

		$this->assertNull($config);
	}

	public function testGetConfig() {
		$config = $this->createMock(Config::class);
		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('load')
			->willReturn($config);

		$cfg = $this->manager->getConfig();

		$this->assertSame($config, $cfg);
	}

	public function testDeprovision() {
		$config = new TestConfig();
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('deleteProvisionedAccounts');
		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('load')
			->willReturn($config);
		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('save')
			->willReturn($config);

		$this->manager->deprovision();

		$this->assertEquals(false, $config->jsonSerialize()['active']);
	}

	public function testImportConfig() {
		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('save');

		$this->manager->importConfig([
			'email' => '%USERID%@domain.com',
		]);
	}

	public function testUpdatePasswordNotProvisioned() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->with($user)
			->willThrowException($this->createMock(DoesNotExistException::class));

		$this->manager->updatePassword($user, '123456');
	}

	public function testUpdatePassword() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$account = $this->createMock(MailAccount::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($account);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($account);

		$this->manager->updatePassword($user, '123456');
	}

	public function testNewProvisioning() {
		$this->mock->getParameter('configMapper')
			->expects($this->once())
			->method('save');

		$this->manager->newProvisioning(
			'%USERID%@domain.com',
			'%USERID%@domain.com',
			'mx.domain.com',
			993,
			'ssl',
			'%USERID%@domain.com',
			'mx.domain.com',
			567,
			'tls'
		);
	}
}
