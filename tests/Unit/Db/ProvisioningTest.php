<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Provisioning;
use OCP\IUser;

/**
 * @covers \OCA\Mail\Db\Provisioning
 */
class ProvisioningTest extends TestCase {

	private function createUser(): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');
		$user->method('getEMailAddress')->willReturn('user1@domain.tld');
		return $user;
	}

	private function createProvisioning(): Provisioning {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%USERID%@domain.tld');
		$provisioning->setImapUser('%USERID%');
		$provisioning->setSmtpUser('%USERID%');
		$provisioning->setSieveUser('%USERID%');
		return $provisioning;
	}

	public function testJsonSerialize(): void {
		$provisioning = new Provisioning();

		$data = $provisioning->jsonSerialize();

		self::assertArrayHasKey('masterPasswordEnabled', $data);
	}

	public function testBuildUsersWithoutMasterUser(): void {
		$provisioning = $this->createProvisioning();
		$user = $this->createUser();

		self::assertSame('user1', $provisioning->buildImapUser($user));
		self::assertSame('user1', $provisioning->buildSmtpUser($user));
		self::assertSame('user1', $provisioning->buildSieveUser($user));
	}

	public function testBuildUsersWithMasterUser(): void {
		$provisioning = $this->createProvisioning();
		$provisioning->setMasterPasswordEnabled(true);
		$provisioning->setMasterUser('*masteruser');
		$user = $this->createUser();

		self::assertSame('user1*masteruser', $provisioning->buildImapUser($user));
		self::assertSame('user1*masteruser', $provisioning->buildSmtpUser($user));
		self::assertSame('user1*masteruser', $provisioning->buildSieveUser($user));
	}

	public function testBuildUsersFallsBackToEmailTemplate(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%USERID%@domain.tld');
		$provisioning->setMasterPasswordEnabled(true);
		$provisioning->setMasterUser('#masteruser');
		$user = $this->createUser();

		self::assertSame('user1@domain.tld#masteruser', $provisioning->buildImapUser($user));
		self::assertSame('user1@domain.tld#masteruser', $provisioning->buildSmtpUser($user));
		self::assertSame('user1@domain.tld#masteruser', $provisioning->buildSieveUser($user));
	}

	public function testBuildUsersIgnoresMasterUserWhenMasterPasswordDisabled(): void {
		$provisioning = $this->createProvisioning();
		$provisioning->setMasterPasswordEnabled(false);
		$provisioning->setMasterUser('*masteruser');
		$user = $this->createUser();

		self::assertSame('user1', $provisioning->buildImapUser($user));
		self::assertSame('user1', $provisioning->buildSmtpUser($user));
		self::assertSame('user1', $provisioning->buildSieveUser($user));
	}

	public function testEnableMasterPasswordWithoutMasterUser(): void {
		$provisioning = new Provisioning();

		$provisioning->enableMasterPassword('mypassword', '');

		self::assertTrue($provisioning->getMasterPasswordEnabled());
		self::assertSame('mypassword', $provisioning->getMasterPassword());
		self::assertNull($provisioning->getMasterUser());
	}

	public function testEnableMasterPasswordKeepsPlaceholderPassword(): void {
		$provisioning = new Provisioning();
		$provisioning->setMasterPassword('stored');

		$provisioning->enableMasterPassword(Provisioning::MASTER_PASSWORD_PLACEHOLDER, '*masteruser');

		self::assertSame('stored', $provisioning->getMasterPassword());
		self::assertSame('*masteruser', $provisioning->getMasterUser());
	}

	public function testDisableMasterPasswordMarksFieldsUpdated(): void {
		$provisioning = new Provisioning();

		$provisioning->disableMasterPassword();

		self::assertFalse($provisioning->getMasterPasswordEnabled());
		self::assertNull($provisioning->getMasterPassword());
		self::assertNull($provisioning->getMasterUser());
		self::assertEqualsCanonicalizing(
			['masterPasswordEnabled', 'masterPassword', 'masterUser'],
			array_keys($provisioning->getUpdatedFields()),
		);
	}

}
