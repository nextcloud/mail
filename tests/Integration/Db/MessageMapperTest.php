<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
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
				$qb2->expr()->like('in_reply_to', $qb2->createNamedParameter("<>", IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
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

		$this->mapper->deleteByUid($mailbox, 1, 5);

		$messages = $this->mapper->findByUids($mailbox, range(1, 10));
		self::assertCount(8, $messages);
	}
}
