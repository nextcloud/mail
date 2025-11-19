<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use function random_int;

/**
 * @group DB
 * @covers \OCA\Mail\Db\MailAccountMapper
 */
class MailAccountMapperTest extends TestCase {
	use DatabaseTransaction;

	private ?\OCA\Mail\Db\MailAccountMapper $mapper = null;

	/** @var IDBConnection */
	private $db;

	private ?\OCA\Mail\Db\MailAccount $account = null;

	/**
	 * Initialize Mapper
	 */
	public function setup(): void {
		parent::setUp();
		$this->db = \OCP\Server::get(\OCP\IDBConnection::class);
		$this->mapper = new MailAccountMapper($this->db);

		$this->account = new MailAccount();
		$this->account->setName('Peter Parker');
		$this->account->setInboundHost('mail.marvel.com');
		$this->account->setInboundPort(159);
		$this->account->setInboundUser('spiderman');
		$this->account->setInboundPassword('xxxxxxxx');
		$this->account->setInboundSslMode('tls');
		$this->account->setEmail('peter.parker@marvel.com');
		$this->account->setOutboundHost('smtp.marvel.com');
		$this->account->setOutboundPort(458);
		$this->account->setOutboundUser('spiderman');
		$this->account->setOutboundPassword('xxxx');
		$this->account->setOutboundSslMode('ssl');
		$this->account->setUserId('user12345');
		$this->account->setEditorMode('plaintext');
		$this->account->setProvisioningId(null);
		$this->account->setOrder(27);
	}

	public function testFind(): void {
		/** @var MailAccount $b */
		$b = $this->mapper->insert($this->account);

		$result = $this->mapper->find($b->getUserId(), $b->getId());
		$this->assertEquals($b->toJson(), $result->toJson());

		$result = $this->mapper->findByUserId($b->getUserId());
		$c = array_filter($result, function (\OCA\Mail\Db\MailAccount $a) use ($b): bool {
			/** @var MailAccount $a */
			return $a->getId() === $b->getId();
		});
		$c = array_pop($c);
		$this->assertEquals($b->toJson(), $c->toJson());
	}

	public function testSave(): void {
		$a = $this->account;

		// test insert
		$b = $this->mapper->save($a);
		$this->assertNotNull($b);
		$this->assertNotNull($a->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($a->getId(), $b->getId());

		// update the entity
		$b->setEmail('spiderman@marvel.com');
		$c = $this->mapper->save($b);
		$this->assertNotNull($c);
		$this->assertNotNull($c->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($b->getId(), $c->getId());
	}

	public function testDeleteProvisionedOrphans(): void {
		$this->account->setProvisioningId(random_int(1, 10000));
		$this->mapper->insert($this->account);

		$this->mapper->deleteProvisionedOrphanAccounts();

		$this->expectException(DoesNotExistException::class);
		$this->mapper->findById($this->account->getId());
	}
}
