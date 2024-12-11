<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class LocalMessageMapperTest extends TestCase {
	use DatabaseTransaction;
	use ImapTestAccount;

	/** @var IDBConnection */
	private $db;

	/** @var LocalMessageMapper */
	private $mapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	/** @var LocalMessage */
	private $entity;

	/** @var MailAccount */
	private $account;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$recipientMapper = new RecipientMapper(
			$this->db
		);
		$this->mapper = new LocalMessageMapper(
			$this->db,
			$this->createMock(LocalAttachmentMapper::class),
			$recipientMapper
		);

		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();

		$this->account = $this->createTestAccount();

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$this->entity = $this->mapper->insert($message);
	}

	public function testFindAllForUser(): void {
		$result = $this->mapper->getAllForUser($this->getTestAccountUserId());

		$this->assertCount(1, $result);
		$row = $result[0];
		$this->assertEquals(LocalMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(2, $row->getAliasId());
		$this->assertEquals($this->account->getId(), $row->getAccountId());
		$this->assertEquals('subject', $row->getSubject());
		$this->assertEquals('message', $row->getBodyHtml());
		$this->assertEquals('abc', $row->getInReplyToMessageId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertEmpty($row->getRecipients());
	}

	/**
	 * @depends testFindAllForUser
	 */
	public function testFindById(): void {
		$row = $this->mapper->findById($this->entity->getId(), $this->account->getUserId(), LocalMessage::TYPE_OUTGOING);

		$this->assertEquals(LocalMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(2, $row->getAliasId());
		$this->assertEquals($this->account->getId(), $row->getAccountId());
		$this->assertEquals('subject', $row->getSubject());
		$this->assertEquals('message', $row->getBodyHtml());
		$this->assertEquals('abc', $row->getInReplyToMessageId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertEmpty($row->getRecipients());
	}

	public function testFindByIdNotFound(): void {
		$this->expectException(DoesNotExistException::class);
		$this->mapper->findById(1337, $this->account->getUserId(), LocalMessage::TYPE_DRAFT);
	}

	/**
	 * @depends testFindById
	 */
	public function testDeleteWithRecipients(): void {
		$this->mapper->deleteWithRecipients($this->entity);

		$result = $this->mapper->getAllForUser($this->getTestAccountUserId());

		$this->assertEmpty($result);
	}

	public function testSaveWithRecipient(): void {
		// cleanup
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setAliasId(3);
		$message->setSendAt(3);
		$message->setSubject('savedWithRelated');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcdefg');
		$recipient = new Recipient();
		$recipient->setEmail('wizard@stardew-valley.com');
		$recipient->setLabel('M. Rasmodeus');
		$recipient->setType(Recipient::TYPE_TO);
		$to = [$recipient];

		$this->mapper->saveWithRecipients($message, $to, [], []);

		$results = $this->mapper->getAllForUser($this->account->getUserId());
		$row = $results[0];
		$this->assertEquals(LocalMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(3, $row->getAliasId());
		$this->assertEquals($this->account->getId(), $row->getAccountId());
		$this->assertEquals('savedWithRelated', $row->getSubject());
		$this->assertEquals('message', $row->getBodyHtml());
		$this->assertEquals('abcdefg', $row->getInReplyToMessageId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertCount(1, $row->getRecipients());
	}

	public function testUpdateWithRecipients(): void {
		$results = $this->mapper->getAllForUser($this->account->getUserId());
		$this->assertEmpty($results[0]->getRecipients());
		// cleanup
		$recipient = new Recipient();
		$recipient->setEmail('wizard@stardew-valley.com');
		$recipient->setLabel('M. Rasmodeus');
		$recipient->setType(Recipient::TYPE_TO);
		$recipient2 = new Recipient();
		$recipient2->setEmail('penny@stardew-valley.com');
		$recipient2->setLabel('Penny');
		$recipient2->setType(Recipient::TYPE_TO);
		$to = [$recipient, $recipient2];

		$this->mapper->updateWithRecipients($results[0], $to, [], []);

		$results = $this->mapper->getAllForUser($this->account->getUserId());
		$this->assertCount(2, $results[0]->getRecipients());
	}

	public function testUpdateWithRecipientOnlyOne(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setAliasId(3);
		$message->setSendAt(3);
		$message->setSubject('savedWithRelated');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcdefg');
		$recipient = new Recipient();
		$recipient->setEmail('wizard@stardew-valley.com');
		$recipient->setLabel('M. Rasmodeus');
		$recipient->setType(Recipient::TYPE_TO);
		$result = $this->mapper->saveWithRecipients($message, [$recipient], [], []);
		$rr = $result->getRecipients();
		$this->assertEquals($recipient->getEmail(), $rr[0]->getEmail());

		$recipient2 = new Recipient();
		$recipient2->setEmail('penny@stardew-valley.com');
		$recipient2->setLabel('Penny');
		$recipient2->setType(Recipient::TYPE_TO);
		$result = $this->mapper->updateWithRecipients($result, [$recipient2], [], []);
		$rr = $result->getRecipients();
		$this->assertEquals($recipient2->getEmail(), $rr[0]->getEmail());
		$this->assertCount(1, $result->getRecipients());
	}
}
