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
use OCP\LDAP\ILDAPProvider;
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

	public function testDisabledAppDoesUnprovision() {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce'
		]);
		$configs = [new Provisioning()];
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(false);
		$this->mock->getParameter('aliasMapper')
			->expects($this->once())
			->method('deleteProvisionedAliasesByUid')
			->with($user->getUID());
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('deleteProvisionedAccountsByUid')
			->with($user->getUID());

		$result = $this->manager->provisionSingleUser($configs, $user);
		$this->assertFalse($result);
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
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
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
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
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
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
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
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
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
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
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
		$user = $this->createStub(IUser::class);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->with($user)
			->willThrowException($this->createMock(DoesNotExistException::class));

		$this->manager->updatePassword($user, '123456', []);
	}

	public function testUpdateLoginPassword(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createStub(IUser::class);
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
		$user = $this->createStub(IUser::class);
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
		$user = $this->createStub(IUser::class);
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

	private function ldapPlaceholderConfig(): Provisioning {
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('*');
		$config->setEmailTemplate('%LDAP:sAMAccountName%@batman.com');
		$config->setImapUser('%LDAP:sAMAccountName%');
		$config->setSmtpUser('%LDAP:sAMAccountName%');
		return $config;
	}

	public function testProvisionSingleUserResolvesLdapPlaceholders(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'LDAP',
		]);
		$configs = [$this->ldapPlaceholderConfig()];
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$ldapProvider = $this->createMock(ILDAPProvider::class);
		$ldapProvider->expects($this->once())
			->method('getUserAttribute')
			->with('bruce', 'sAMAccountName')
			->willReturn('BWAYNE');
		$this->mock->getParameter('ldapProviderFactory')
			->method('isAvailable')
			->willReturn(true);
		$this->mock->getParameter('ldapProviderFactory')
			->method('getLDAPProvider')
			->willReturn($ldapProvider);
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->with($this->callback(static function (MailAccount $account) {
				return $account->getEmail() === 'BWAYNE@batman.com'
					&& $account->getInboundUser() === 'BWAYNE'
					&& $account->getOutboundUser() === 'BWAYNE';
			}))
			->willReturn($mailAccount);

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertTrue($result);
	}

	private function expectNoAccountWrite(): void {
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('findProvisionedAccount');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('update');
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->never())
			->method('insert');
	}

	public function provideUnresolvableLdapValues(): array {
		return [
			'empty' => [''],
			'control characters' => ["BWAYNE\r\nX"],
			'invalid UTF-8' => ["BWAYNE\xFF"],
			'space' => ['Bruce Wayne'],
			'unicode line separator' => ["BWAYNE\u{2028}X"],
			'comma escaping the domain' => ['ceo@evil.example,x'],
			'angle brackets escaping the domain' => ['a@b.com<c@d.com>'],
			'longer than the account column' => [str_repeat('a', 65)],
		];
	}

	/**
	 * @dataProvider provideUnresolvableLdapValues
	 */
	public function testProvisionSingleUserSkipsUnusableLdapValue(string $ldapValue): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'LDAP',
		]);
		$configs = [$this->ldapPlaceholderConfig()];
		$ldapProvider = $this->createMock(ILDAPProvider::class);
		$ldapProvider->expects($this->once())
			->method('getUserAttribute')
			->willReturn($ldapValue);
		$this->mock->getParameter('ldapProviderFactory')
			->method('isAvailable')
			->willReturn(true);
		$this->mock->getParameter('ldapProviderFactory')
			->method('getLDAPProvider')
			->willReturn($ldapProvider);
		$this->mock->getParameter('logger')
			->expects($this->atLeastOnce())
			->method('warning');
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->expectNoAccountWrite();

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertFalse($result);
	}

	public function testProvisionSingleUserSkipsWhenLdapThrows(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'LDAP',
		]);
		$configs = [$this->ldapPlaceholderConfig()];
		$ldapProvider = $this->createMock(ILDAPProvider::class);
		$ldapProvider->expects($this->once())
			->method('getUserAttribute')
			->willThrowException(new \Exception('User id not found in LDAP'));
		$this->mock->getParameter('ldapProviderFactory')
			->method('isAvailable')
			->willReturn(true);
		$this->mock->getParameter('ldapProviderFactory')
			->method('getLDAPProvider')
			->willReturn($ldapProvider);
		$this->mock->getParameter('logger')
			->expects($this->atLeastOnce())
			->method('warning');
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->expectNoAccountWrite();

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertFalse($result);
	}

	public function testProvisionSingleUserSkipsWhenLdapUnavailable(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'LDAP',
		]);
		$configs = [$this->ldapPlaceholderConfig()];
		$this->mock->getParameter('ldapProviderFactory')
			->method('isAvailable')
			->willReturn(false);
		$this->mock->getParameter('ldapProviderFactory')
			->expects($this->never())
			->method('getLDAPProvider');
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->expectNoAccountWrite();

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertFalse($result);
	}

	public function testProvisionSingleUserNonLdapBackendSkipsLookup(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'Database',
		]);
		$configs = [$this->ldapPlaceholderConfig()];
		$this->mock->getParameter('ldapProviderFactory')
			->method('isAvailable')
			->willReturn(true);
		$this->mock->getParameter('ldapProviderFactory')
			->expects($this->never())
			->method('getLDAPProvider');
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->expectNoAccountWrite();

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertFalse($result);
	}

	public function testProvisionSingleUserWithoutLdapTokensSkipsFactory(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com',
			'getUID' => 'bruce',
			'getBackendClassName' => 'LDAP',
		]);
		$config = new Provisioning();
		$config->setId(1);
		$config->setProvisioningDomain('*');
		$config->setEmailTemplate('%USERID%@batman.com');
		$configs = [$config];
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$this->mock->getParameter('ldapProviderFactory')
			->expects($this->never())
			->method('isAvailable');
		$this->mock->getParameter('appManager')
			->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('findProvisionedAccount')
			->willReturn($mailAccount);
		$this->mock->getParameter('mailAccountMapper')
			->expects($this->once())
			->method('update')
			->willReturn($mailAccount);

		$result = $this->manager->provisionSingleUser($configs, $user);

		$this->assertTrue($result);
	}
}
