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

	public function testJsonSerialize(): void {
		$provisioning = new Provisioning();

		$data = $provisioning->jsonSerialize();

		self::assertArrayHasKey('masterPasswordEnabled', $data);
	}

	private function createUser(?string $email = 'jane@corp.example'): IUser {
		return $this->createConfiguredMock(IUser::class, [
			'getUID' => 'jane',
			'getEMailAddress' => $email,
		]);
	}

	public function testBuildEmailWithLdapValue(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:sAMAccountName%@corp.example');

		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:sAMAccountName%' => 'jdoe',
		]);

		self::assertSame('jdoe@corp.example', $email);
	}

	public function testBuildEmailWithMultipleDistinctAttributes(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%.%LDAP:ou%@corp.example');

		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:uid%' => 'jdoe',
			'%LDAP:ou%' => 'sales',
		]);

		self::assertSame('jdoe.sales@corp.example', $email);
	}

	public function testBuildEmailWithRepeatedToken(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%+%LDAP:uid%@corp.example');

		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:uid%' => 'jdoe',
		]);

		self::assertSame('jdoe+jdoe@corp.example', $email);
	}

	public function testBuildEmailUnresolvedLdapStaysLiteral(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:sAMAccountName%@corp.example');

		$email = $provisioning->buildEmail($this->createUser(), []);

		self::assertSame('%LDAP:sAMAccountName%@corp.example', $email);
	}

	public function testBuildEmailMixedPlaceholders(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%USERID%.%LDAP:uid%.%EMAIL%');

		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:uid%' => 'jdoe',
		]);

		self::assertSame('jane.jdoe.jane@corp.example', $email);
	}

	public function testBuildImapUserFallsBackToEmailTemplateWithLdapValues(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%@corp.example');

		$imapUser = $provisioning->buildImapUser($this->createUser(), [
			'%LDAP:uid%' => 'jdoe',
		]);

		self::assertSame('jdoe@corp.example', $imapUser);
	}

	public function testLdapAttributesInTemplates(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%@corp.example');
		$provisioning->setImapUser('%LDAP:sAMAccountName%');
		$provisioning->setSmtpUser('%LDAP:sAMAccountName%');
		$provisioning->setSieveEnabled(true);
		$provisioning->setSieveUser('%LDAP:mail-alias%');

		$attributes = $provisioning->ldapAttributesInTemplates();

		self::assertSame(['uid', 'sAMAccountName', 'mail-alias'], $attributes);
	}

	public function testLdapAttributesInTemplatesIgnoresDisabledSieve(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%@corp.example');
		$provisioning->setSieveEnabled(false);
		$provisioning->setSieveUser('%LDAP:mail-alias%');

		$attributes = $provisioning->ldapAttributesInTemplates();

		self::assertSame(['uid'], $attributes);
	}

	public function testLdapAttributesInTemplatesIgnoresInvalidTokens(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:%@corp.example');
		$provisioning->setImapUser('%LDAP:foo bar%');
		$provisioning->setSmtpUser('%ldap:uid%');

		$attributes = $provisioning->ldapAttributesInTemplates();

		self::assertSame([], $attributes);
	}

	public function testBuildEmailDoesNotMergeAdjacentPlaceholders(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:dept%USERID%@corp.example');

		$attributes = $provisioning->ldapAttributesInTemplates();
		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:dept%' => 'sales',
		]);

		self::assertSame(['dept'], $attributes);
		self::assertSame('salesUSERID%@corp.example', $email);
	}

	public function testBuildEmailDoesNotSubstituteResolvedValues(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%LDAP:uid%@corp.example');

		$email = $provisioning->buildEmail($this->createUser(), [
			'%LDAP:uid%' => '%USERID%',
		]);

		self::assertSame('%USERID%@corp.example', $email);
	}

	public function testBuildEmailKeepsEmailTokenWithoutUserEmail(): void {
		$provisioning = new Provisioning();
		$provisioning->setEmailTemplate('%USERID%.%EMAIL%');

		$email = $provisioning->buildEmail($this->createUser(null));

		self::assertSame('jane.%EMAIL%', $email);
	}

	public function testFindMalformedLdapPlaceholders(): void {
		$malformed = Provisioning::findMalformedLdapPlaceholders('%ldap:uid%.%LDAP:foo_bar%.%LDAP:mail;binary%.%LDAP:%');

		self::assertSame(['%ldap:uid%', '%LDAP:foo_bar%', '%LDAP:mail;binary%', '%LDAP:%'], $malformed);
	}

	public function testFindMalformedLdapPlaceholdersAcceptsValidSyntax(): void {
		$malformed = Provisioning::findMalformedLdapPlaceholders('%LDAP:sAMAccountName%.%LDAP:mail-alias%@corp.example');

		self::assertSame([], $malformed);
	}

	public function testFindMalformedLdapPlaceholdersWithoutTemplate(): void {
		self::assertSame([], Provisioning::findMalformedLdapPlaceholders(null));
	}

	public function testLdapAttributesInTemplatesWithoutTemplates(): void {
		$provisioning = new Provisioning();

		$attributes = $provisioning->ldapAttributesInTemplates();

		self::assertSame([], $attributes);
	}

}
