<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Tests\Integration\Db\Alias;
use OCA\Mail\Tests\Integration\Db\MockObject;
use OCP\IDBConnection;
use Psr\Log\NullLogger;

class ProvisioningMapperTest extends TestCase {

	private ProvisioningMapper $mapper;

	public function setup(): void {
		parent::setUp();

		$this->mapper = new ProvisioningMapper(
			$this->createMock(IDBConnection::class),
			new NullLogger(),
		);
	}

	public function testValidateEmptyHost(): void {
		$data = [
			'provisioningDomain' => 'static.test',
			'emailTemplate' => '%USERID%@static.test',
			'imapUser' => '%EMAIL%',
			'imapHost' => '',
			'imapPort' => '143',
			'imapSslMode' => 'none',
			'smtpUser' => '%EMAIL%',
			'smtpHost' => '',
			'smtpPort' => '25',
			'smtpSslMode' => 'none',
		];

		$exceptionWasThrown = false;

		try {
			$this->mapper->validate($data);
		} catch (ValidationException $e) {
			$exceptionWasThrown = true;
			$fields = $e->getFields();
			$this->assertCount(2, $fields);
			$this->assertArrayHasKey('imapHost', $fields);
			$this->assertArrayHasKey('smtpHost', $fields);
		}

		$this->assertTrue($exceptionWasThrown);
	}

	public function testValidateLdapAliasesProvisioningNeedsAttribute(): void {
		$data = [
			'provisioningDomain' => 'static.test',
			'emailTemplate' => '%USERID%@static.test',
			'imapUser' => '%EMAIL%',
			'imapHost' => 'static.test',
			'imapPort' => '143',
			'imapSslMode' => 'none',
			'smtpUser' => '%EMAIL%',
			'smtpHost' => 'static.test',
			'smtpPort' => '25',
			'smtpSslMode' => 'none',
			'ldapAliasesProvisioning' => true,
			'ldapAliasesAttribute' => ''
		];

		$exceptionWasThrown = false;

		try {
			$this->mapper->validate($data);
		} catch (ValidationException $e) {
			$exceptionWasThrown = true;
			$fields = $e->getFields();
			$this->assertCount(1, $fields);
			$this->assertArrayHasKey('ldapAliasesAttribute', $fields);
		}

		$this->assertTrue($exceptionWasThrown);
	}

	public function testValidateMasterPasswordNeedsPassword(): void {
		$data = [
			'provisioningDomain' => 'static.test',
			'emailTemplate' => '%USERID%@static.test',
			'imapUser' => '%EMAIL%',
			'imapHost' => 'static.test',
			'imapPort' => '143',
			'imapSslMode' => 'none',
			'smtpUser' => '%EMAIL%',
			'smtpHost' => 'static.test',
			'smtpPort' => '25',
			'smtpSslMode' => 'none',
			'masterPasswordEnabled' => true,
			'masterPassword' => '',
		];

		$exceptionWasThrown = false;

		try {
			$this->mapper->validate($data);
		} catch (ValidationException $e) {
			$exceptionWasThrown = true;
			$fields = $e->getFields();
			$this->assertCount(1, $fields);
			$this->assertArrayHasKey('masterPassword', $fields);
		}

		$this->assertTrue($exceptionWasThrown);
	}

	public function testValidateMasterPasswordWitUserNeedsPasswordAndSeparator(): void {
		$data = [
			'provisioningDomain' => 'static.test',
			'emailTemplate' => '%USERID%@static.test',
			'imapUser' => '%EMAIL%',
			'imapHost' => 'static.test',
			'imapPort' => '143',
			'imapSslMode' => 'none',
			'smtpUser' => '%EMAIL%',
			'smtpHost' => 'static.test',
			'smtpPort' => '25',
			'smtpSslMode' => 'none',
			'masterPasswordEnabled' => true,
			'masterPassword' => '',
			'masterUser' => 'master',
			'masterUserSeparator' => '',
		];

		$exceptionWasThrown = false;

		try {
			$this->mapper->validate($data);
		} catch (ValidationException $e) {
			$exceptionWasThrown = true;
			$fields = $e->getFields();
			$this->assertCount(2, $fields);
			$this->assertArrayHasKey('masterPassword', $fields);
			$this->assertArrayHasKey('masterUserSeparator', $fields);
		}

		$this->assertTrue($exceptionWasThrown);
	}


}
