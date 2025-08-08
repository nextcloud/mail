<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Service;

use OCA\Mail\Db\ContextChat\TaskMapper;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class TaskServiceTest extends TestCase {

	private IDBConnection $db;

	/** @var TaskMapper */
	private $mapper;

	/** @var TaskService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OCP\Server::get(IDBConnection::class);
		$this->mapper = new TaskMapper(
			$this->db
		);
		$this->service = new TaskService(
			$this->mapper
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
		$task = $this->service->findNext();
		$this->assertEquals(1, $task->getMailboxId());
		$this->assertEquals(1, $task->getLastMessageId());
	}

	public function testUpdateOrCreateWithUpdateToEarlierMessage(): void {
		$this->insert(2, 2);
		$this->insert(3, 3);
		$this->service->updateOrCreate(2, 1);
		$task = $this->service->findNext();
		$this->assertEquals(2, $task->getMailboxId());
		$this->assertEquals(1, $task->getLastMessageId());
	}

	public function testUpdateOrCreateWithUpdateToLaterMessage(): void {
		$this->insert(2, 2);
		$this->insert(3, 3);
		$this->service->updateOrCreate(2, 3); // will be noop because 3 is later than 2
		$task = $this->service->findNext();
		$this->assertEquals(2, $task->getMailboxId());
		$this->assertEquals(2, $task->getLastMessageId());
	}

	public function testUpdateOrCreateNoUpdate(): void {
		$this->service->updateOrCreate(2, 3);
		$task = $this->service->findNext();
		$this->assertEquals(2, $task->getMailboxId());
		$this->assertEquals(3, $task->getLastMessageId());
	}

	// public function testDelete(): void {
	// 	$this->insert(1, 1);
	// 	$this->insert(2, 2); // task to delete
	// 	$this->insert(3, 3);
	// 	$task = $this->service->delete(10);
	// 	$this->assertEquals(2, $task->getMailboxId());
	// 	$this->assertEquals(2, $task->getLastMessageId());

	// 	$task = $this->service->delete(12);
	// 	$this->assertNull($task);
	// }
}
