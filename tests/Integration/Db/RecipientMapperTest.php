<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class RecipientMapperTest extends TestCase {
	use ImapTestAccount;

	/** @var IDBConnection */
	private $db;

	/** @var RecipientMapper */
	private $mapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	/** @var Recipient */
	private $inboxRecipient;

	/** @var Recipient */
	private $outboxRecipient;

	/** @var LocalMessage  */
	private $message;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new RecipientMapper(
			$this->db
		);
		$this->localMessageMapper = new LocalMessageMapper(
			$this->db,
			$this->createMock(LocalAttachmentMapper::class),
			$this->createMock(RecipientMapper::class)
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->localMessageMapper->getTableName());
		$delete->execute();

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$this->message = $this->localMessageMapper->insert($message);

		$this->outboxRecipient = new Recipient();
		$this->outboxRecipient->setLocalMessageId($this->message->getId());
		$this->outboxRecipient->setEmail('doc@stardew-clinic.com');
		$this->outboxRecipient->setType(Recipient::TYPE_TO);
		$this->outboxRecipient->setLabel('Dr. Harvey');
		$this->mapper->insert($this->outboxRecipient);

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
		$this->mapper->deleteForLocalMailbox($this->message->getId());
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
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$message = $this->localMessageMapper->insert($message);

		$recipient = new Recipient();
		$recipient->setEmail('penny@stardewvalleylibrary.edu');
		$recipient->setLabel('Penny');
		$this->mapper->saveRecipients($message->getId(), [$recipient], Recipient::TYPE_FROM);

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
}
