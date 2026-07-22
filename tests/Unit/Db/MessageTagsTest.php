<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MessageTags;

class MessageTagsTest extends TestCase {
	private MessageTags $messageTags;

	protected function setUp(): void {
		parent::setUp();

		$this->messageTags = new MessageTags();
	}

	public function testConstructor(): void {
		$messageTags = new MessageTags();

		$this->assertInstanceOf(MessageTags::class, $messageTags);
	}

	public function testSetAndGetImapMessageId(): void {
		$imapMessageId = 'abc123def456';

		$this->messageTags->setImapMessageId($imapMessageId);
		$result = $this->messageTags->getImapMessageId();

		$this->assertSame($imapMessageId, $result);
	}

	public function testSetAndGetTagId(): void {
		$tagId = 42;

		$this->messageTags->setTagId($tagId);
		$result = $this->messageTags->getTagId();

		$this->assertSame($tagId, $result);
	}

	public function testJsonSerialize(): void {
		$imapMessageId = 'msg123';
		$tagId = 5;

		$this->messageTags->setImapMessageId($imapMessageId);
		$this->messageTags->setTagId($tagId);

		$result = $this->messageTags->jsonSerialize();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('imapMessageId', $result);
		$this->assertArrayHasKey('tagId', $result);
		$this->assertSame($imapMessageId, $result['imapMessageId']);
		$this->assertSame($tagId, $result['tagId']);
	}

	public function testJsonSerializeStructure(): void {
		$imapMessageId = 'test-msg';
		$tagId = 99;

		$this->messageTags->setImapMessageId($imapMessageId);
		$this->messageTags->setTagId($tagId);

		$serialized = $this->messageTags->jsonSerialize();

		$this->assertCount(3, $serialized);
		$this->assertArrayHasKey('id', $serialized);
		$this->assertArrayHasKey('imapMessageId', $serialized);
		$this->assertArrayHasKey('tagId', $serialized);
		$this->assertSame($imapMessageId, $serialized['imapMessageId']);
		$this->assertSame($tagId, $serialized['tagId']);
	}

	public function testSetAndGetImapMessageIdWithEmptyString(): void {
		$imapMessageId = '';

		$this->messageTags->setImapMessageId($imapMessageId);
		$result = $this->messageTags->getImapMessageId();

		$this->assertSame($imapMessageId, $result);
	}

	public function testSetAndGetTagIdZero(): void {
		$tagId = 0;

		$this->messageTags->setTagId($tagId);
		$result = $this->messageTags->getTagId();

		$this->assertSame($tagId, $result);
	}

	public function testMultipleSettersGetters(): void {
		$imapMessageId1 = 'msg1';
		$tagId1 = 1;
		$imapMessageId2 = 'msg2';
		$tagId2 = 2;

		$this->messageTags->setImapMessageId($imapMessageId1);
		$this->messageTags->setTagId($tagId1);
		$this->assertSame($imapMessageId1, $this->messageTags->getImapMessageId());
		$this->assertSame($tagId1, $this->messageTags->getTagId());

		$this->messageTags->setImapMessageId($imapMessageId2);
		$this->messageTags->setTagId($tagId2);
		$this->assertSame($imapMessageId2, $this->messageTags->getImapMessageId());
		$this->assertSame($tagId2, $this->messageTags->getTagId());
	}
}
