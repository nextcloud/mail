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
use OCA\Mail\Db\Provisioning;
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
		/** @var MockObject $manager */
		$this->manager = $this->mock->getService();
	}

	public function testProvision() {
		$this->mock->getParameter('userManager')
			->expects($this->once())
			->method('callForAllUsers');

		$cnt = $this->manager->provision();

		$this->assertEquals(0, $cnt);
	}

	public function testUpdateProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('batman.com');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$account = new MailAccount();
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($account);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($account);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$account = new MailAccount();
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('batman.com');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willThrowException($this->createMock(DoesNotExistException::class));
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('insert')
			->willReturn($account);
		$this->mock->getParameter('tagMapper')
			->expects($this->once())
			->method('createDefaultTags')
			->with($account);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testUpdateProvisionSingleUserWithWildcard() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$account = new MailAccount();
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('*');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$account = $this->createMock(MailAccount::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($account);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($account);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUserWithWildcard() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$account = new MailAccount();
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('*');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willThrowException($this->createMock(DoesNotExistException::class));
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('insert')
			->willReturn($account);
		$this->mock->getParameter('tagMapper')
			->expects($this->once())
			->method('createDefaultTags')
			->with($account);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUserNoDomainMatch() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$account = new MailAccount();
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('arkham-asylum.com');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('findProvisionedAccount');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('update');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('insert');
		$this->mock->getParameter('tagMapper')
			->expects($this->never())
			->method('createDefaultTags');

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertFalse($result);
	}

	public function testDeprovision() {
		$config = new Provisioning();
		$config->setProvisioningDomain('*');
		$config->setId(1);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('deleteProvisionedAccounts');

		$this->manager->deprovision($config);
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

	public function testNewProvisioning(): void {
		$config = new Provisioning();
		$this->mock->getParameter('provisioningMapper')
			->expects($this->once())
			->method('validate')
			->willReturn($config);
		$this->mock->getParameter('provisioningMapper')
			->expects($this->once())
			->method('insert')
			->willReturn($config);

		$result = $this->manager->newProvisioning([
			'active' => true,
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
			'sieveUser' => '',
			'sieveHost' => '',
			'sievePort' => 0,
			'sieveSslMode' => 'tls'
		]);

		self::assertInstanceOf(Provisioning::class, $result);
	}
}
