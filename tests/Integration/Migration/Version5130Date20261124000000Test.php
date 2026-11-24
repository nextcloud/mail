<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Migration;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Migration\Version5130Date20261124000000;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;

/**
 * @group DB
 */
final class Version5130Date20261124000000Test extends TestCase {
	use DatabaseTransaction;

	private IDBConnection $db;
	private MailAccountMapper $mapper;
	private Version5130Date20261124000000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = new MailAccountMapper($this->db);
		$this->migration = new Version5130Date20261124000000($this->db);
	}

	public function testClearsOnlyDeviatingSmtpUserOfOauthAccounts(): void {
		$staleOauth = $this->insertAccount('xoauth2', 'shared@domain.tld', 'old@domain.tld');
		$matchingOauth = $this->insertAccount('xoauth2', 'user@domain.tld', 'user@domain.tld');
		$emptyOauth = $this->insertAccount('xoauth2', 'other@domain.tld', null);
		$password = $this->insertAccount('password', 'basic@domain.tld', 'login-name');

		$this->migration->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->createMock(ISchemaWrapper::class),
			[],
		);

		$this->assertNull($this->mapper->findById($staleOauth->getId())->getOutboundUser());
		$this->assertSame('user@domain.tld', $this->mapper->findById($matchingOauth->getId())->getOutboundUser());
		$this->assertNull($this->mapper->findById($emptyOauth->getId())->getOutboundUser());
		$this->assertSame('login-name', $this->mapper->findById($password->getId())->getOutboundUser());
	}

	private function insertAccount(string $authMethod, string $email, ?string $smtpUser): MailAccount {
		$account = new MailAccount();
		$account->setUserId('user');
		$account->setName('Test');
		$account->setEmail($email);
		$account->setAuthMethod($authMethod);
		$account->setOutboundUser($smtpUser);
		return $this->mapper->insert($account);
	}
}
