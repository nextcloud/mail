<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\Avatar\Avatar;

class MessageTest extends TestCase {
	protected function setUp(): void {
	}

	public function testNewForMessageId(): void {
		$expected = '<abc@123.com>';
		$message = new Message();

		$message->setMessageId($expected);
		$message->setThreadRootId($expected);

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertEquals($expected, $message->getThreadRootId());
	}

	public function testNewWithMessageIdNoAngleBrackets(): void {
		$expected = '<abc@123.com>';
		$noBrackets = 'abc@123.com';
		$message = new Message();

		$message->setMessageId($noBrackets);
		$message->setThreadRootId($noBrackets);

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertEquals($expected, $message->getThreadRootId());
	}

	public function testNewWithMessageIdOneAngleBrackets(): void {
		$expected = '<abc@123.com>';
		$noBrackets = '<abc@123.com';
		$message = new Message();

		$message->setMessageId($noBrackets);
		$message->setThreadRootId($noBrackets);

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertEquals($expected, $message->getThreadRootId());

		$noBrackets = 'abc@123.com>';
		$message = new Message();

		$message->setMessageId($noBrackets);
		$message->setThreadRootId($noBrackets);

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertEquals($expected, $message->getThreadRootId());
	}

	public function testThreadrootIdNull(): void {
		$expected = '<abc@123.com>';
		$message = new Message();

		$message->setMessageId($expected);
		$message->setThreadRootId(null);

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertNotNull($message->getThreadRootId());
		$this->assertEquals($expected, $message->getThreadRootId());
	}

	public function testThreadrootIdSet(): void {
		$expected = '<abc@123.com>';
		$message = new Message();

		$message->setMessageId($expected);
		$message->setThreadRootId('<cde789@test.com>');

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertNotNull($message->getThreadRootId());
		$this->assertEquals('<cde789@test.com>', $message->getThreadRootId());
	}

	public function testThreadrootIdEmptyString(): void {
		$expected = '<abc@123.com>';
		$message = new Message();

		$message->setMessageId($expected);
		$message->setThreadRootId('');

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertNotNull($message->getThreadRootId());
		$this->assertEquals($expected, $message->getThreadRootId());
	}

	public function testSetInReplyToEmpty(): void {
		$expected = '<abc@123.com>';
		$message = new Message();

		$message->setMessageId($expected);
		$message->setThreadRootId('');
		$message->setInReplyTo('');

		$this->assertEquals($expected, $message->getMessageId());
		$this->assertNotNull($message->getThreadRootId());
		$this->assertEquals($expected, $message->getThreadRootId());
		$this->assertNull($message->getInReplyTo());
	}

	public function testSetAvatar(): void {
		$expected = new Avatar(
			'http://example.com/avatar.png',
			'image/png',
			true
		);
		$message = new Message();

		$message->setAvatar($expected);

		$this->assertEquals($expected, $message->getAvatar());
	}

	public function testSetFetchAvatarFromClient(): void {
		$message = new Message();

		$message->setFetchAvatarFromClient(true);

		$this->assertTrue($message->jsonSerialize()['fetchAvatarFromClient']);
	}
}
