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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class RecipientMapperTest extends TestCase {
	use DatabaseTransaction;
	use ImapTestAccount;

	/** @var IDBConnection */
	private $db;

	/** @var RecipientMapper */
	private $mapper;

	private LocalMessageMapper $localMessageMapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	/** @var Recipient */
	private $inboxRecipient;

	/** @var LocalMessage */
	private $message;
	/** @var MailAccount */
	private $account;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new RecipientMapper(
			$this->db
		);
		$this->localMessageMapper = new LocalMessageMapper(
			$this->db,
			$this->createMock(LocalAttachmentMapper::class),
			$this->mapper
		);

		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();

		$qb2 = $this->db->getQueryBuilder();
		$delete2 = $qb2->delete($this->localMessageMapper->getTableName());
		$delete2->executeStatement();

		$this->account = $this->createTestAccount();

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$this->message = $this->localMessageMapper->insert($message);

		$outboxRecipient = new Recipient();
		$outboxRecipient->setLocalMessageId($this->message->getId());
		$outboxRecipient->setEmail('doc@stardew-clinic.com');
		$outboxRecipient->setType(Recipient::TYPE_TO);
		$outboxRecipient->setLabel('Dr. Harvey');
		$this->mapper->insert($outboxRecipient);

		$inboxRecipientTwo = new Recipient();
		$inboxRecipientTwo->setLocalMessageId($this->message->getId());
		$inboxRecipientTwo->setEmail('pierre@stardewvalley.com');
		$inboxRecipientTwo->setType(Recipient::TYPE_CC);
		$inboxRecipientTwo->setLabel("Pierre's General Store");
		$this->mapper->insert($inboxRecipientTwo);
	}

	public function testFindRecipients(): void {
		$result = $this->mapper->findByLocalMessageId($this->message->getId());
		$this->assertCount(2, $result);
	}

	/**
	 * @depends testFindRecipients
	 */
	public function testFindAllRecipients(): void {
		$result = $this->mapper->findByLocalMessageIds([$this->message->getId(),789,789]);
		$this->assertCount(2, $result);
	}

	/**
	 * @depends testFindAllRecipients
	 */
	public function testFindAllRecipientsEmpty(): void {
		$result = $this->mapper->findByLocalMessageIds([12,57842]);
		$this->assertEmpty($result);
	}

	/**
	 * @depends testFindAllRecipientsEmpty
	 */
	public function testDeleteForLocalMailbox(): void {
		$this->mapper->deleteForLocalMessage($this->message->getId());
		$result = $this->mapper->findByLocalMessageId($this->message->getId());
		$this->assertEmpty($result);
	}

	/**
	 * @depends testDeleteForLocalMailbox
	 */
	public function testSaveRecipients(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$message = $this->localMessageMapper->insert($message);

		$recipient = new Recipient();
		$recipient->setEmail('penny@stardewvalleylibrary.edu');
		$recipient->setLabel('Penny');
		$recipient->setType(Recipient::TYPE_FROM);
		$this->mapper->saveRecipients($message->getId(), [$recipient]);

		$results = $this->mapper->findByLocalMessageId($message->getId());
		$this->assertCount(1, $results);

		/** @var Recipient $entity */
		$entity = $results[0];
		$this->assertEquals($message->getId(), $entity->getLocalMessageId());
		$this->assertNull($entity->getMessageId());
		$this->assertEquals(Recipient::TYPE_FROM, $entity->getType());
		$this->assertEquals('Penny', $entity->getLabel());
		$this->assertEquals('penny@stardewvalleylibrary.edu', $entity->getEmail());
	}

	public function testUpdateRecipients(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$message = $this->localMessageMapper->insert($message);

		$penny = new Recipient();
		$penny->setEmail('penny@stardewvalleylibrary.edu');
		$penny->setLabel('Penny');
		$penny->setType(Recipient::TYPE_TO);
		$this->mapper->saveRecipients($message->getId(), [$penny]);

		$results = $this->mapper->findByLocalMessageId($message->getId());
		$this->assertCount(1, $results);

		$message = $this->localMessageMapper->findById($message->getId(), $this->getTestAccountUserId(), LocalMessage::TYPE_OUTGOING);

		$pierre = new Recipient();
		$pierre->setLabel('Pierre');
		$pierre->setEmail('generalstore@stardewvalley.com');
		$pierre->setType(Recipient::TYPE_TO);
		$to = [$penny, $pierre];
		$cc = [];
		$bcc = [];
		$this->mapper->updateRecipients($message->getId(), $message->getRecipients(), $to, $cc, $bcc);

		$results = $this->mapper->findByLocalMessageId($message->getId());
		$this->assertCount(2, $results);
	}
}
