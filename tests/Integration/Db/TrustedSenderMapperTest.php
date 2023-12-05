<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use ChristophWurst\Nextcloud\Testing\TestUser;
use OCA\Mail\Db\TrustedSenderMapper;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;

class TrustedSenderMapperTest extends TestCase {
	use DatabaseTransaction, TestUser;

	/** @var IDBConnection */
	private $db;

	/** @var IUser */
	private $user;

	/** @var TrustedSenderMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		/** @var IDBConnection $db */
		$this->db = Server::get(IDBConnection::class);
		$this->user = $this->createTestUser();

		$this->mapper = new TrustedSenderMapper(
			$this->db
		);
	}

	public function testExistsButDoesNot(): void {
		$exists = $this->mapper->exists($this->user->getUID(), "christoph@next.cloud");

		$this->assertFalse($exists);
	}

	public function testIndividualExists(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('christoph@next.cloud'),
			])
			->executeStatement();

		$exists = $this->mapper->exists($uid, "christoph@next.cloud");

		$this->assertTrue($exists);
	}

	public function testDomainExists(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('next.cloud'),
				'type' => $qb->createNamedParameter('domain'),

			])
			->executeStatement();

		$exists = $this->mapper->exists($uid, "christoph@next.cloud");

		$this->assertTrue($exists);
	}

	public function testCreateIndividual(): void {
		$uid = $this->user->getUID();
		$this->mapper->create(
			$uid,
			"christoph@next.cloud",
			'individual'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_trusted_senders')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter("christoph@next.cloud"))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertCount(1, $rows);
	}

	public function testRemoveIndividual(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('christoph@next.cloud'),
				'type' => $qb->createNamedParameter('individual'),
			])
			->executeStatement();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('next.cloud'),
				'type' => $qb->createNamedParameter('domain'),
			])
			->executeStatement();

		$this->mapper->remove(
			$uid,
			"christoph@next.cloud",
			'individual'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_trusted_senders')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter("christoph@next.cloud")),
				$qb->expr()->eq('type', $qb->createNamedParameter("individual"))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertEmpty($rows);
	}

	public function testRemoveDomain(): void {
		$uid = $this->user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('christoph@next.cloud'),
				'type' => $qb->createNamedParameter('individual'),
			])
			->executeStatement();
		$qb->insert('mail_trusted_senders')
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter('next.cloud'),
				'type' => $qb->createNamedParameter('domain'),
			])
			->executeStatement();

		$this->mapper->remove(
			$uid,
			"next.cloud",
			'domain'
		);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_trusted_senders')
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter("next.cloud")),
				$qb->expr()->eq('type', $qb->createNamedParameter("domain"))
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$this->assertEmpty($rows);
	}
}
