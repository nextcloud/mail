<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use OCA\Mail\Db\ImipDataMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ImipDataMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var ImipDataMapper */
	private $mapper;
	/** @var IDBConnection */
	private $db;
	/** @var ITimeFactory */
	private $time;

	private int $timestamp = 1234567890;


	public function setUp(): void {
		parent::setUp();
		$this->db = \OCP\Server::get(\OCP\IDBConnection::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')->willReturnCallback(fn () => $this->timestamp);
		$this->mapper = new ImipDataMapper($this->db, $this->time);
	}

	private function insertMessage(int $uid, int $mailbox_id): int {
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert('mail_messages')
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc' . $uid . $mailbox_id . '@123.com>'),
				'mailbox_id' => $qb->createNamedParameter($mailbox_id, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter($this->time->getTime(), IQueryBuilder::PARAM_INT),
				'in_reply_to' => $qb->createNamedParameter('<>')
			]);
		$insert->executeStatement();

		return $qb->getLastInsertId();
	}

	public function testMarkAsImipMessage(): void {
		$messageId = $this->insertMessage(100, 1);

		$this->mapper->markAsImipMessage($messageId);

		$row = $this->mapper->findByMessageId($messageId);
		$this->assertNotNull($row);
		$this->assertSame($messageId, $row->getImipMessageId());
		$this->assertFalse($row->getError());
		$this->assertNull($row->getProcessedAt());
	}

	public function testMarkProcessed(): void {
		$messageId = $this->insertMessage(101, 1);
		$this->mapper->markAsImipMessage($messageId);

		$this->mapper->markProcessed($messageId, true);

		$row = $this->mapper->findByMessageId($messageId);
		$this->assertNotNull($row);
		$this->assertTrue($row->getError());
		$this->assertSame($this->timestamp, $row->getProcessedAt());
	}

	public function testMarkProcessedBulk(): void {
		$messageId1 = $this->insertMessage(102, 1);
		$messageId2 = $this->insertMessage(103, 1);

		$this->mapper->markAsImipMessage($messageId1);
		$this->mapper->markAsImipMessage($messageId2);

		$message1 = new Message();
		$message1->setId($messageId1);
		$message1->setImipError(true);

		$message2 = new Message();
		$message2->setId($messageId2);
		$message2->resetUpdatedFields();

		$this->mapper->markProcessedBulk($message1, $message2);

		$row1 = $this->mapper->findByMessageId($messageId1);
		$this->assertNotNull($row1->getProcessedAt(), 'Message with an updated field should be marked processed');

		$row2 = $this->mapper->findByMessageId($messageId2);
		$this->assertNull($row2->getProcessedAt(), 'Message with no updated fields should not be marked processed');
	}


}
