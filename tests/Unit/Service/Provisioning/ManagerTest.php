<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function testProvision(): void {
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('batman.com');
		$config->setEmailTemplate('%USER%@batman.com');

		$this->mock->getParameter('provisioningMapper')
			->expects($this->once())
			->method('getAll')
			->willReturn([$config]);

		$this->mock->getParameter('userManager')
			->expects($this->once())
			->method('callForAllUsers');

		$count = $this->manager->provision();

		$this->assertEquals(0, $count);
	}

	public function testProvisionSkipWithoutConfigurations(): void {
		$this->mock->getParameter('userManager')
			->expects($this->never())
			->method('callForAllUsers');

		$count = $this->manager->provision();

		$this->assertEquals(0, $count);
	}

	public function testUpdateProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce'
		]);
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('batman.com');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($mailAccount);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUser() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce'
		]);
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
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
			->willReturn($mailAccount);
		$this->mock->getParameter('tagMapper')
			->expects($this->once())
			->method('createDefaultTags')
			->with($mailAccount);
		$this->mock->getParameter('accountService')
			->expects($this->once())
			->method('scheduleBackgroundJobs');

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testUpdateProvisionSingleUserWithWildcard() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce.wayne'
		]);
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('*');
		$config->setEmailTemplate('%USER%@batman.com');
		$configs = [$config];
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($mailAccount);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUserWithWildcard() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce'
		]);
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
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
			->willReturn($mailAccount);
		$this->mock->getParameter('tagMapper')
			->expects($this->once())
			->method('createDefaultTags')
			->with($mailAccount);

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertTrue($result);
	}

	public function testProvisionSingleUserNoDomainMatch() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
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

	public function testUpdatePasswordNotProvisioned(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->with($user)
			->willThrowException($this->createMock(DoesNotExistException::class));

		$this->manager->updatePassword($user, '123456', []);
	}

	public function testUpdateLoginPassword(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$config = new Provisioning();
		$config->setProvisioningDomain(Provisioning::WILDCARD);
		$config->setMasterPasswordEnabled(false);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($mailAccount);

		$this->manager->updatePassword($user, '123456', [$config]);
	}

	public function testUpdateMasterPasswordWithExistingLoginPassword(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$config = new Provisioning();
		$config->setProvisioningDomain(Provisioning::WILDCARD);
		$config->setMasterPasswordEnabled(true);
		$config->setMasterPassword('topsecret');
		$this->mock->getParameter('crypto')
			->expects(self::atLeast(1))
			->method('encrypt')
			->with('topsecret')
			->willReturn('tercespot');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($mailAccount);

		$this->manager->updatePassword($user, '123456', [$config]);
	}

	public function testUpdateMasterPasswordWithoutLoginPassword(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$config = new Provisioning();
		$config->setProvisioningDomain(Provisioning::WILDCARD);
		$config->setMasterPasswordEnabled(true);
		$config->setMasterPassword('topsecret');
		$this->mock->getParameter('crypto')
			->expects(self::atLeast(1))
			->method('encrypt')
			->with('topsecret')
			->willReturn('tercespot');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($mailAccount);

		$this->manager->updatePassword($user, null, [$config]);
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
