<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class MailboxMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var IDBConnection */
	private $db;

	/** @var MailboxMapper */
	private $mapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->mapper = new MailboxMapper(
			$this->db,
			$this->timeFactory
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();
	}

	public function testFindAllNoData() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);

		$result = $this->mapper->findAll($account);

		$this->assertEmpty($result);
	}

	public function testFindAll() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		foreach (range(1, 10) as $i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'name' => $qb->createNamedParameter("folder$i"),
					'account_id' => $qb->createNamedParameter($i <= 5 ? 13 : 14, IQueryBuilder::PARAM_INT),
					'sync_new_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
					'sync_changed_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
					'sync_vanished_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
					'delimiter' => $qb->createNamedParameter('.'),
					'messages' => $qb->createNamedParameter($i * 100, IQueryBuilder::PARAM_INT),
					'unseen' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'selectable' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
					'name_hash' => $qb->createNamedParameter(md5("folder$i")),
				]);
			$insert->executeStatement();
		}

		$result = $this->mapper->findAll($account);

		$this->assertCount(5, $result);
	}

	public function testNoInboxFound() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		$this->expectException(DoesNotExistException::class);

		$this->mapper->find($account, 'INBOX');
	}

	public function testFindInbox() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'name' => $qb->createNamedParameter('INBOX'),
				'account_id' => $qb->createNamedParameter(13, IQueryBuilder::PARAM_INT),
				'sync_new_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_changed_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_vanished_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'delimiter' => $qb->createNamedParameter('.'),
				'messages' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'unseen' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'selectable' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				'name_hash' => $qb->createNamedParameter(md5('INBOX')),
			]);
		$insert->executeStatement();

		$result = $this->mapper->find($account, 'INBOX');

		$this->assertSame('INBOX', $result->getName());
	}

	public function testMailboxesWithTrailingSpace() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);

		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'name' => $qb->createNamedParameter('Test'),
				'account_id' => $qb->createNamedParameter(13, IQueryBuilder::PARAM_INT),
				'sync_new_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_changed_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_vanished_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'delimiter' => $qb->createNamedParameter('.'),
				'messages' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'unseen' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'selectable' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				'name_hash' => $qb->createNamedParameter(md5('Test')),
			]);
		$insert->executeStatement();

		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'name' => $qb->createNamedParameter('Test '),
				'account_id' => $qb->createNamedParameter(13, IQueryBuilder::PARAM_INT),
				'sync_new_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_changed_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'sync_vanished_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
				'delimiter' => $qb->createNamedParameter('.'),
				'messages' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'unseen' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'selectable' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				'name_hash' => $qb->createNamedParameter(md5('Test ')),
			]);
		$insert->executeStatement();

		$resultA = $this->mapper->find($account, 'Test');
		$this->assertSame('Test', $resultA->getName());

		$resultB = $this->mapper->find($account, 'Test ');
		$this->assertSame('Test ', $resultB->getName());
	}
}
