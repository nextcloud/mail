<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use ChristophWurst\Nextcloud\Testing\TestUser;
use OCA\Mail\Db\InternalAddressMapper;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;

class InternalAddressMapperTest extends TestCase {
	use DatabaseTransaction, TestUser;

	/** @var IDBConnection */
	private $db;

	/** @var IUser */
	private $user;

	/** @var InternalAddressMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		/** @var IDBConnection $db */
		$this->db = Server::get(IDBConnection::class);
		$this->user = $this->createTestUser();

		$this->mapper = new InternalAddressMapper(
			$this->db
		);
	}

	public function testDoesntExist(): void {
		$exists = $this->mapper->exists($this->user->getUID(), 'hamza@next.cloud');

		$this->assertFalse($exists);
	}

	public function testIndividualExists(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_internal_address')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'address' => $qb->createNamedParameter('hamza@next.cloud'),
				'type' => $qb->createNamedParameter('individual')

			])
			->executeStatement();

		$exists = $this->mapper->exists($uid, 'hamza@next.cloud');

		$this->assertTrue($exists);
	}

	public function testDomainExists(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_internal_address')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'address' => $qb->createNamedParameter('next.cloud'),
				'type' => $qb->createNamedParameter('domain'),

			])
			->executeStatement();

		$exists = $this->mapper->exists($uid, 'hamza@next.cloud');

		$this->assertTrue($exists);
	}

	public function testCreateIndividual(): void {
		$uid = $this->user->getUID();
		$this->mapper->create(
			$uid,
			'hamza@next.cloud',
			'individual'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_internal_address')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter('hamza@next.cloud'))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertCount(1, $rows);
	}

	public function testRemoveIndividual(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_internal_address')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'address' => $qb->createNamedParameter('hamza@next.cloud'),
				'type' => $qb->createNamedParameter('individual'),
			])
			->executeStatement();

		$this->mapper->remove(
			$uid,
			'hamza@next.cloud',
			'individual'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_internal_address')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter('hamza@next.cloud'))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertEmpty($rows);
	}

	public function testCreateDomain(): void {
		$uid = $this->user->getUID();
		$this->mapper->create(
			$uid,
			'next.cloud',
			'domain'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_internal_address')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter('next.cloud')),
				$qb->expr()->eq('type', $qb->createNamedParameter('domain'))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertCount(1, $rows);
	}

	public function testRemoveDomain(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_internal_address')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'address' => $qb->createNamedParameter('next.cloud'),
				'type' => $qb->createNamedParameter('domain'),
			])
			->executeStatement();

		$this->mapper->remove(
			$uid,
			'next.cloud',
			'domain'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_internal_address')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter('next.cloud')),
				$qb->expr()->eq('type', $qb->createNamedParameter('domain'))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertEmpty($rows);
	}

	public function testFindAll(): void {
		$uid = $this->user->getUID();

		$this->db->beginTransaction();

		$data = [
			['user_id' => $uid, 'address' => 'hamza@next.cloud', 'type' => 'individual'],
			['user_id' => $uid, 'address' => 'christoph@next.cloud', 'type' => 'individual'],
		];
		$sql = 'INSERT INTO oc_mail_internal_address (user_id, address, type) VALUES (:user_id, :address, :type)';
		$stmt = $this->db->prepare($sql);

		foreach ($data as $row) {
			$stmt->execute($row);
		}

		$this->db->commit();

		$results = $this->mapper->findAll($uid);

		$this->assertCount(2, $results);
		$this->assertEquals($results[0]->getAddress(), 'hamza@next.cloud');
		$this->assertEquals($results[1]->getAddress(), 'christoph@next.cloud');
	}
}
