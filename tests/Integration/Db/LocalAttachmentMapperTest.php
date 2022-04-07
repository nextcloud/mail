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

		$attachment = LocalAttachment::fromParams([
			'fileName' => 'slimes_in_the_mines.jpeg',
			'mimeType' => 'image/jpeg',
			'userId' => 'user45678',
			'createdAt' => $this->timeFactory->getTime()
		]);
		$attachment2 = LocalAttachment::fromParams([
			'fileName' => 'prismatic_shard.png',
			'mimeType' => 'image/png',
			'userId' => 'dontFindMe',
			'createdAt' => $this->timeFactory->getTime()
		]);
		$attachment = $this->mapper->insert($attachment);
		$attachment2 = $this->mapper->insert($attachment2);
		$this->attachmentIds = [$attachment->getId(), $attachment2->getId()];

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(3);
		$message->setSendAt(3);
		$message->setSubject('testSaveLocalAttachments');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcdefg');
		$message = $this->localMessageMapper->insert($message);
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
		$this->localMessageIds = [$message->getId(), $message2->getId()];
	}

	public function testSaveAndFindLocalAttachments(): void {
		$this->mapper->saveLocalMessageAttachments($this->localMessageIds[0], $this->attachmentIds);
		$foundAttachments = $this->mapper->findByLocalMessageId($this->localMessageIds[0]);

		$this->assertCount(2, $foundAttachments);
	}

	public function testDeleteForLocalMessage(): void {
		$this->mapper->saveLocalMessageAttachments($this->localMessageIds[0], $this->attachmentIds);
		$foundAttachments = $this->mapper->findByLocalMessageId($this->localMessageIds[0]);

		$this->assertCount(2, $foundAttachments);

		$this->mapper->deleteForLocalMessage($this->localMessageIds[0]);

		$result = $this->mapper->findByLocalMessageId($this->localMessageIds[0]);
		$this->assertEmpty($result);
	}

	public function testFind(): void {
		$this->mapper->saveLocalMessageAttachments($this->localMessageIds[0], $this->attachmentIds);
		$foundAttachment = $this->mapper->find('user45678', $this->attachmentIds[0]);

		$this->assertEquals('slimes_in_the_mines.jpeg', $foundAttachment->getFileName());
		$this->assertEquals('image/jpeg', $foundAttachment->getMimeType());
		$this->assertEquals($this->localMessageIds[0], $foundAttachment->getLocalMessageId());
		$this->assertEquals('user45678', $foundAttachment->getUserId());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find('user45678', $this->attachmentIds[1]);
	}

	public function testFindByLocalMessageIds(): void {
		$this->mapper->saveLocalMessageAttachments($this->localMessageIds[0], [$this->attachmentIds[0]]);
		$this->mapper->saveLocalMessageAttachments($this->localMessageIds[1], [$this->attachmentIds[1]]);

		$foundAttachments = $this->mapper->findByLocalMessageIds($this->localMessageIds);
		$this->assertCount(2, $foundAttachments);
		$this->assertEquals($this->localMessageIds[0], $foundAttachments[0]->getLocalMessageId());
		$this->assertEquals($this->localMessageIds[1], $foundAttachments[1]->getLocalMessageId());
	}
}
