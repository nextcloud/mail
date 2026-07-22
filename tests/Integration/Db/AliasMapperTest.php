<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\IDBConnection;

/**
 * @group DB
 */
class AliasMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var AliasMapper */
	private $mapper;

	/** @var IDBConnection */
	private $db;

	/** @var Alias */
	private $alias;

	/**
	 * Initialize Mapper
	 */
	public function setup(): void {
		parent::setUp();
		$this->db = \OCP\Server::get(\OCP\IDBConnection::class);
		$this->mapper = new AliasMapper($this->db);
	}

	public function testFind() {
		$accountMapper = new MailAccountMapper($this->db);
		$account = new MailAccount();
		$account->setName('Peter Parker');
		$account->setInboundHost('mail.marvel.com');
		$account->setInboundPort(159);
		$account->setInboundUser('spiderman');
		$account->setInboundPassword('xxxxxxxx');
		$account->setInboundSslMode('tls');
		$account->setEmail('peter.parker@marvel.com');
		$account->setOutboundHost('smtp.marvel.com');
		$account->setOutboundPort(458);
		$account->setOutboundUser('spiderman');
		$account->setOutboundPassword('xxxx');
		$account->setOutboundSslMode('ssl');
		$account->setUserId('user12345');
		$a = $accountMapper->insert($account);
		$this->alias = new Alias();
		$this->alias->setAccountId($a->getId());
		$this->alias->setAlias('alias@marvel.com');
		$this->alias->setName('alias');
		$this->alias->setSignature('Kind regards<br>Alias');
		/** @var Alias $b */
		$b = $this->mapper->insert($this->alias);

		$result = $this->mapper->find($b->getId(), $account->getUserId());

		$this->assertEquals(
			[
				'accountId' => $this->alias->getAccountId(),
				'name' => $this->alias->getName(),
				'alias' => $this->alias->getAlias(),
				'id' => $this->alias->getId(),
				'signature' => $this->alias->getSignature(),
			], [
				'accountId' => $result->getAccountId(),
				'name' => $result->getName(),
				'alias' => $result->getAlias(),
				'id' => $result->getId(),
				'signature' => $this->alias->getSignature(),
			]
		);
	}

	public function testDeleteProvisionedAliasesByUid() {
		$accountMapper = new MailAccountMapper($this->db);

		// Create first account with provisioning_id
		$account1 = new MailAccount();
		$account1->setName('User One Account');
		$account1->setInboundHost('mail.example.com');
		$account1->setInboundPort(993);
		$account1->setInboundUser('user1');
		$account1->setInboundPassword('password1');
		$account1->setInboundSslMode('ssl');
		$account1->setEmail('user1@example.com');
		$account1->setOutboundHost('smtp.example.com');
		$account1->setOutboundPort(587);
		$account1->setOutboundUser('user1');
		$account1->setOutboundPassword('password1');
		$account1->setOutboundSslMode('tls');
		$account1->setUserId('user1');
		$account1->setProvisioningId(1);
		$account1 = $accountMapper->insert($account1);

		// Create second account with provisioning_id (different user)
		$account2 = new MailAccount();
		$account2->setName('User Two Account');
		$account2->setInboundHost('mail.example.com');
		$account2->setInboundPort(993);
		$account2->setInboundUser('user2');
		$account2->setInboundPassword('password2');
		$account2->setInboundSslMode('ssl');
		$account2->setEmail('user2@example.com');
		$account2->setOutboundHost('smtp.example.com');
		$account2->setOutboundPort(587);
		$account2->setOutboundUser('user2');
		$account2->setOutboundPassword('password2');
		$account2->setOutboundSslMode('tls');
		$account2->setUserId('user2');
		$account2->setProvisioningId(2);
		$account2 = $accountMapper->insert($account2);

		// Create third account without provisioning_id
		$account3 = new MailAccount();
		$account3->setName('User One Non-Provisioned');
		$account3->setInboundHost('mail.example.com');
		$account3->setInboundPort(993);
		$account3->setInboundUser('user1-personal');
		$account3->setInboundPassword('password3');
		$account3->setInboundSslMode('ssl');
		$account3->setEmail('user1-personal@example.com');
		$account3->setOutboundHost('smtp.example.com');
		$account3->setOutboundPort(587);
		$account3->setOutboundUser('user1-personal');
		$account3->setOutboundPassword('password3');
		$account3->setOutboundSslMode('tls');
		$account3->setUserId('user1');
		$account3 = $accountMapper->insert($account3);

		// Create aliases for account1 (user1, provisioned)
		$alias1_1 = new Alias();
		$alias1_1->setAccountId($account1->getId());
		$alias1_1->setAlias('user1-alias1@example.com');
		$alias1_1->setName('User 1 Alias 1');
		$alias1_1->setSignature('Sig 1');
		$alias1_1 = $this->mapper->insert($alias1_1);

		$alias1_2 = new Alias();
		$alias1_2->setAccountId($account1->getId());
		$alias1_2->setAlias('user1-alias2@example.com');
		$alias1_2->setName('User 1 Alias 2');
		$alias1_2->setSignature('Sig 2');
		$alias1_2 = $this->mapper->insert($alias1_2);

		// Create aliases for account2 (user2, provisioned)
		$alias2_1 = new Alias();
		$alias2_1->setAccountId($account2->getId());
		$alias2_1->setAlias('user2-alias1@example.com');
		$alias2_1->setName('User 2 Alias 1');
		$alias2_1->setSignature('Sig 3');
		$alias2_1 = $this->mapper->insert($alias2_1);

		// Create aliases for account3 (user1, non-provisioned)
		$alias3_1 = new Alias();
		$alias3_1->setAccountId($account3->getId());
		$alias3_1->setAlias('user1-personal-alias@example.com');
		$alias3_1->setName('User 1 Personal Alias');
		$alias3_1->setSignature('Sig 4');
		$alias3_1 = $this->mapper->insert($alias3_1);

		// Verify setup: all aliases exist by finding them (will throw if not found)
		$this->mapper->find($alias1_1->getId(), 'user1');
		$this->mapper->find($alias1_2->getId(), 'user1');
		$this->mapper->find($alias2_1->getId(), 'user2');
		$this->mapper->find($alias3_1->getId(), 'user1');

		// Delete provisioned aliases for user1
		$this->mapper->deleteProvisionedAliasesByUid('user1');

		// Verify that provisioned aliases for user1 are deleted
		try {
			$this->mapper->find($alias1_1->getId(), 'user1');
			$this->fail('Alias 1.1 should have been deleted');
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			$this->assertTrue(true);
		}

		try {
			$this->mapper->find($alias1_2->getId(), 'user1');
			$this->fail('Alias 1.2 should have been deleted');
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			$this->assertTrue(true);
		}

		// Verify that provisioned aliases for user2 still exist
		$this->mapper->find($alias2_1->getId(), 'user2');

		// Verify that non-provisioned aliases for user1 still exist
		$this->mapper->find($alias3_1->getId(), 'user1');
	}

	public function testDeleteProvisionedAliasesByUidWithNoResults(): void {
		// This test verifies that the method handles empty results gracefully
		// Should not throw an exception when deleting for a non-existent user
		$this->mapper->deleteProvisionedAliasesByUid('non_existent_user');
		$this->assertTrue(true);
	}
}
