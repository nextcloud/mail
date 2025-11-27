<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\ContextChat\TaskMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class TaskMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var IDBConnection */
	private $db;

	private TaskMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OCP\Server::get(IDBConnection::class);
		$this->mapper = new TaskMapper(
			$this->db,
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();
	}

	private function insert(int $mailboxId, int $lastMessageId): void {
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'mailbox_id' => $qb->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT),
				'last_message_id' => $qb->createNamedParameter($lastMessageId, IQueryBuilder::PARAM_INT),
			]);
		$insert->executeStatement();
	}

	public function testFindNext(): void {
		$this->insert(1, 1);
		$this->insert(2, 2);
		$this->insert(3, 3);
		$task = $this->mapper->findNext();
		$this->assertEquals(1, $task->getMailboxId());
		$this->assertEquals(1, $task->getLastMessageId());
	}

	public function testFindById(): void {
		$this->insert(1, 1);
		$this->insert(2, 2);
		$this->insert(3, 3);
		$task = $this->mapper->findById(2);
		$this->assertEquals(2, $task->getMailboxId());
		$this->assertEquals(2, $task->getLastMessageId());
	}

	public function testFindByMailbox(): void {
		$this->insert(1, 1);
		$this->insert(2, 2);
		$this->insert(3, 3);
		$task = $this->mapper->findByMailbox(2);
		$this->assertEquals(2, $task->getMailboxId());
		$this->assertEquals(2, $task->getLastMessageId());
	}
}
