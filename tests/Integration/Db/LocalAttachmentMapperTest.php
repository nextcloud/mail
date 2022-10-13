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
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\RecipientMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class LocalAttachmentMapperTest extends TestCase {
	/** @var IDBConnection */
	private $db;

	/** @var MailAccount */
	private $account;

	/** @var LocalAttachmentMapper */
	private $mapper;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var array */
	private $attachments;

	/** @var string */
	private $user1 = 'user45678';
	/** @var string  */
	private $user2 = 'dontFindMe';

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new LocalAttachmentMapper(
			$this->db
		);
		$this->localMessageMapper = new LocalMessageMapper(
			$this->db,
			$this->mapper,
			$this->createMock(RecipientMapper::class)
		);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$attachment1 = LocalAttachment::fromParams([
			'fileName' => 'slimes_in_the_mines.jpeg',
			'mimeType' => 'image/jpeg',
			'userId' => $this->user1,
			'createdAt' => $this->timeFactory->getTime()
		]);
		$attachment2 = LocalAttachment::fromParams([
			'fileName' => 'prismatic_shard.png',
			'mimeType' => 'image/png',
			'userId' => $this->user2,
			'createdAt' => $this->timeFactory->getTime()
		]);
		$attachment3 = LocalAttachment::fromParams([
			'fileName' => 'slimes_in_the_shard.png',
			'mimeType' => 'image/png',
			'userId' => $this->user1,
			'createdAt' => $this->timeFactory->getTime()
		]);
		$attachment1 = $this->mapper->insert($attachment1);
		$attachment2 = $this->mapper->insert($attachment2);
		$attachment3 = $this->mapper->insert($attachment3);
		$this->attachmentIds = [$attachment1->getId(), $attachment2->getId(), $attachment3->getId()];

		$message1 = new LocalMessage();
		$message1->setType(LocalMessage::TYPE_OUTGOING);
		$message1->setAccountId(1);
		$message1->setAliasId(3);
		$message1->setSendAt(3);
		$message1->setSubject('testSaveLocalAttachments');
		$message1->setBody('message');
		$message1->setHtml(true);
		$message1->setInReplyToMessageId('abcdefg');
		$message1 = $this->localMessageMapper->insert($message1);
		$message2 = new LocalMessage();
		$message2->setType(LocalMessage::TYPE_OUTGOING);
		$message2->setAccountId(1);
		$message2->setAliasId(3);
		$message2->setSendAt(3);
		$message2->setSubject('testSaveLocalAttachments');
		$message2->setBody('message');
		$message2->setHtml(true);
		$message2->setInReplyToMessageId('abcdefg');
		$message2 = $this->localMessageMapper->insert($message2);
		$this->localMessageIds = [$message1->getId(), $message2->getId()];
	}

	public function testSaveAndFindLocalAttachments(): void {
		$this->mapper->saveLocalMessageAttachments($this->user1, $this->localMessageIds[0], $this->attachmentIds);
		$foundAttachments = $this->mapper->findByLocalMessageId($this->user1, $this->localMessageIds[0]);

		$this->assertCount(2, $foundAttachments);
	}

	public function testDeleteForLocalMessage(): void {
		$this->mapper->saveLocalMessageAttachments($this->user1, $this->localMessageIds[0], $this->attachmentIds);
		$foundAttachments = $this->mapper->findByLocalMessageId($this->user1, $this->localMessageIds[0]);

		$this->assertCount(2, $foundAttachments);

		$this->mapper->deleteForLocalMessage($this->user1, $this->localMessageIds[0]);

		$result = $this->mapper->findByLocalMessageId($this->user1, $this->localMessageIds[0]);
		$this->assertEmpty($result);
	}

	public function testFind(): void {
		$this->mapper->saveLocalMessageAttachments($this->user1, $this->localMessageIds[0], $this->attachmentIds);
		$foundAttachment = $this->mapper->find($this->user1, $this->attachmentIds[0]);

		$this->assertEquals('slimes_in_the_mines.jpeg', $foundAttachment->getFileName());
		$this->assertEquals('image/jpeg', $foundAttachment->getMimeType());
		$this->assertEquals($this->localMessageIds[0], $foundAttachment->getLocalMessageId());
		$this->assertEquals($this->user1, $foundAttachment->getUserId());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find($this->user1, $this->attachmentIds[1]);
	}

	public function testFindByLocalMessageIds(): void {
		$this->mapper->saveLocalMessageAttachments($this->user1, $this->localMessageIds[0], [$this->attachmentIds[0]]);
		$this->mapper->saveLocalMessageAttachments($this->user2, $this->localMessageIds[1], [$this->attachmentIds[1]]);

		$foundAttachments = $this->mapper->findByLocalMessageIds($this->localMessageIds);
		$this->assertCount(2, $foundAttachments);
		$this->assertEquals($this->localMessageIds[0], $foundAttachments[0]->getLocalMessageId());
		$this->assertEquals($this->localMessageIds[1], $foundAttachments[1]->getLocalMessageId());
	}
}
