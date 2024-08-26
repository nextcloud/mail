<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_map;
use function range;
use function time;

class MessageMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var IDBConnection */
	private $db;

	/** @var MessageMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$timeFactory = $this->createMock(ITimeFactory::class);
		$tagMapper = $this->createMock(TagMapper::class);
		$performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->mapper = new MessageMapper(
			$this->db,
			$timeFactory,
			$tagMapper,
			$performanceLogger
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();
	}

	private function insertMessage(int $uid, int $mailbox_id): void {
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc' . $uid . $mailbox_id . '@123.com>'),
				'mailbox_id' => $qb->createNamedParameter($mailbox_id, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
				'in_reply_to' => $qb->createNamedParameter('<>')
			]);
		$insert->executeStatement();
	}

	public function testResetInReplyTo() : void {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		array_map(function ($i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'uid' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'message_id' => $qb->createNamedParameter('<abc' . $i . '@123.com>'),
					'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
					'subject' => $qb->createNamedParameter('TEST'),
					'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
					'in_reply_to' => $qb->createNamedParameter('<>')
				]);
			$insert->executeStatement();
		}, range(1, 10));

		array_map(function ($i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'uid' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'message_id' => $qb->createNamedParameter('<abc' . $i . '@123.com>'),
					'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
					'subject' => $qb->createNamedParameter('TEST'),
					'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
					'in_reply_to' => $qb->createNamedParameter('<abc@dgf.com>')
				]);
			$insert->executeStatement();
		}, range(11, 20));

		$result = $this->mapper->resetInReplyTo();

		$this->assertEquals(10, $result);

		$qb2 = $this->db->getQueryBuilder();
		$select = $qb2->select('*')
			->from($this->mapper->getTableName())
			->where(
				$qb2->expr()->like('in_reply_to', $qb2->createNamedParameter('<>', IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			);

		$result = $select->executeQuery();
		$rows = $result->fetchAll();

		$this->assertEmpty($rows);
	}

	public function testResetPreviewDataFlag(): void {
		$uid = time();
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc@123.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
			]);
		$insert->executeStatement();

		$this->mapper->resetPreviewDataFlag();

		$qb2 = $this->db->getQueryBuilder();
		$result = $qb2->select($qb2->func()->count('*'))
			->from($this->mapper->getTableName())
			->where(
				$qb2->expr()->eq('uid', $qb2->createNamedParameter($uid, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb2->expr()->eq('structure_analyzed', $qb2->createNamedParameter(true, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL)
			)
			->executeQuery();
		$cnt = $result->fetchOne();
		$result->closeCursor();
		self::assertEquals(0, $cnt);
	}

	public function testFindIdsByQuery(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$searchQuery = new SearchQuery();
		$sortOrder = 'DESC';
		$qb = $this->db->getQueryBuilder();

		$values = [
			[
				'id' => 1,
				'uid' => $qb->createNamedParameter(267, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc@123.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 1'),
				'sent_at' => $qb->createNamedParameter(1641216000, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 2,
				'uid' => $qb->createNamedParameter(268, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<def@456.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 2'),
				'sent_at' => $qb->createNamedParameter(1641216001, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 3,
				'uid' => $qb->createNamedParameter(269, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<ghi@789.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 3'),
				'sent_at' => $qb->createNamedParameter(1641216003, IQueryBuilder::PARAM_INT),
			],
		];

		foreach ($values as $value) {
			$insert = $qb->insert($this->mapper->getTableName())->values($value);
			$insert->executeStatement();
		}

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, $sortOrder, 3, null);

		self::assertEquals([3,2,1], $result);
	}

	public function testDeleteByUid(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		array_map(function ($i) {
			$this->insertMessage($i, 1);
		}, range(1, 10));

		$this->mapper->deleteByUid($mailbox, 1, 5);

		$messages = $this->mapper->findByUids($mailbox, range(1, 10));
		self::assertCount(8, $messages);
	}

	public function testDeleteDuplicateUids(): void {
		$mailbox1 = new Mailbox();
		$mailbox1->setId(1);
		$mailbox2 = new Mailbox();
		$mailbox2->setId(2);
		$mailbox3 = new Mailbox();
		$mailbox3->setId(3);
		$this->insertMessage(100, 1);
		$this->insertMessage(101, 1);
		$this->insertMessage(101, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(103, 2);
		$this->insertMessage(104, 2);
		$this->insertMessage(104, 2);
		$this->insertMessage(105, 3);

		$this->mapper->deleteDuplicateUids();

		self::assertCount(1, $this->mapper->findByUids($mailbox1, [100]));
		self::assertCount(1, $this->mapper->findByUids($mailbox1, [101]));
		self::assertCount(1, $this->mapper->findByUids($mailbox1, [102]));
		self::assertCount(1, $this->mapper->findByUids($mailbox2, [103]));
		self::assertCount(1, $this->mapper->findByUids($mailbox2, [104]));
		self::assertCount(1, $this->mapper->findByUids($mailbox3, [105]));
	}
}
