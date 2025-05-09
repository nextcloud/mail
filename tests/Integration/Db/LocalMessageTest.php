<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalMessage;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class LocalMessageTest extends TestCase {
	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	protected function setUp(): void {
		$this->timeFactory = $this->createMock(ITimeFactory::class);
	}

	public function testGettersSetters(): void {
		$time = $this->timeFactory->getTime();
		$message = new LocalMessage();

		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSendAt($time);
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('<abcdefg@12345678.com>');

		$this->assertEquals(LocalMessage::TYPE_OUTGOING, $message->getType());
		$this->assertEquals(1, $message->getAccountId());
		$this->assertEquals(2, $message->getAliasId());
		$this->assertEquals($time, $message->getSendAt());
		$this->assertEquals('subject', $message->getSubject());
		$this->assertEquals('message', $message->getBodyHtml());
		$this->assertTrue($message->isHtml());
		$this->assertEquals('<abcdefg@12345678.com>', $message->getInReplyToMessageId());
		$this->assertNull($message->getAttachments());
		$this->assertNull($message->getRecipients());
	}
}
