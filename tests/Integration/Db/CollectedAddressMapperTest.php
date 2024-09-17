<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OC;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCP\IDBConnection;

/**
 * @group DB
 */
class CollectedAddressMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var IDBConnection */
	private $db;

	/** @var string */
	private $userId = 'testuser';

	/** @var CollectedAddressMapper */
	private $mapper;

	/** @var CollectedAddress */
	private $address1;

	/** @var CollectedAddress */
	private $address2;

	/** @var CollectedAddress */
	private $address3;

	protected function setUp(): void {
		parent::setUp();

		$this->db = OC::$server->getDatabaseConnection();
		$this->mapper = new CollectedAddressMapper($this->db);

		$this->address1 = new CollectedAddress();
		$this->address1->setEmail('user1@example.com');
		$this->address1->setDisplayName('User 1');
		$this->address1->setUserId($this->userId);

		$this->address2 = new CollectedAddress();
		$this->address2->setEmail('user2@example.com');
		$this->address2->setDisplayName('User 2');
		$this->address2->setUserId($this->userId);

		$this->address3 = new CollectedAddress();
		$this->address3->setEmail('"User 3" <user3@domain.com>');
		$this->address3->setDisplayName('User 3');
		$this->address3->setUserId($this->userId);

		$sql = 'INSERT INTO *PREFIX*mail_coll_addresses (`email`, `display_name`, `user_id`) VALUES (?, ?, ?)';
		$stmt = $this->db->prepare($sql);

		// Empty DB
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->mapper->getTableName());
		$qb->executeStatement();

		$stmt->execute([
			$this->address1->getEmail(),
			$this->address1->getDisplayName(),
			$this->address1->getUserId(),
		]);
		$this->address1->setId($this->db->lastInsertId('PREFIX*mail_coll_addresses'));
		$stmt->execute([
			$this->address2->getEmail(),
			$this->address2->getDisplayName(),
			$this->address2->getUserId(),
		]);
		$this->address2->setId($this->db->lastInsertId('PREFIX*mail_coll_addresses'));
		$stmt->execute([
			$this->address3->getEmail(),
			$this->address3->getDisplayName(),
			$this->address3->getUserId(),
		]);
		$this->address3->setId($this->db->lastInsertId('PREFIX*mail_coll_addresses'));
	}

	public function matchingData() {
		return [
			['user1@example.com', ['user1@example.com']],
			['examp', ['user1@example.com', 'user2@example.com']],
		];
	}

	/**
	 * @dataProvider matchingData
	 */
	public function testFindMatching($query, $result) {
		$matches = $this->mapper->findMatching($this->userId, $query);

		$this->assertCount(\count($result), $matches);
		$i = 0;
		foreach ($matches as $match) {
			$this->assertInstanceOf(\OCA\Mail\Db\CollectedAddress::class, $match);
			$this->assertContains($match->getEmail(), $result);
			$this->assertEquals($this->userId, $match->getUserId());
			$i++;
		}
	}

	public function insertIfNewData(): array {
		return [
			['user1@example.com', false],
			['user3@example.com', true],
		];
	}

	/**
	 * @dataProvider insertIfNewData
	 */
	public function testExists($email, $expected): void {
		$actual = $this->mapper->insertIfNew($this->userId, $email, null);

		$this->assertSame($expected, $actual);
	}

	public function testGetTotal() {
		$total = $this->mapper->getTotal();

		$this->assertSame(3, $total);
	}

	public function testGetChunk() {
		$chunk = $this->mapper->getChunk();

		$this->assertCount(3, $chunk);
	}

	public function testGetChunkWithOffset() {
		$chunk = $this->mapper->getChunk($this->address2->getId());

		$this->assertCount(2, $chunk);
	}
}
