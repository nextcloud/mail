<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Model;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Model\NewMessageData;

class NewMessageDataTest extends TestCase {
	public function testConstructionFromSimpleRequestData() {
		$account = $this->createMock(Account::class);
		$to = '"Test" <test@domain.com>';
		$cc = '';
		$bcc = '';
		$subject = 'Hello';
		$body = 'Hi!';
		$attachments = [];
		$messageData = NewMessageData::fromRequest($account, $subject, $body, $to, $cc, $bcc, $attachments, false, true);

		$this->assertEquals($account, $messageData->getAccount());
		$this->assertInstanceOf(AddressList::class, $messageData->getTo());
		$this->assertInstanceOf(AddressList::class, $messageData->getCc());
		$this->assertInstanceOf(AddressList::class, $messageData->getBcc());
		$this->assertEquals('Hello', $messageData->getSubject());
		$this->assertEquals('Hi!', $messageData->getBody());
		$this->assertEquals([], $messageData->getAttachments());
		$this->assertFalse($messageData->isHtml());
		$this->assertTrue($messageData->isMdnRequested());
	}

	public function testConstructionFromComplexRequestData() {
		$account = $this->createMock(Account::class);
		$to = '"Test" <test@domain.com>, test2@domain.de';
		$cc = 'test2@domain.at';
		$bcc = '"Test3" <test3@domain.net>';
		$subject = 'Hello';
		$body = 'Hi!';
		$attachments = [];
		$messageData = NewMessageData::fromRequest($account, $subject, $body, $to, $cc, $bcc, $attachments);

		$this->assertEquals($account, $messageData->getAccount());
		$this->assertInstanceOf(AddressList::class, $messageData->getTo());
		$this->assertEquals(['test@domain.com', 'test2@domain.de'], $messageData->getTo()->toHorde()->bare_addresses);
		$this->assertInstanceOf(AddressList::class, $messageData->getCc());
		$this->assertEquals(['test2@domain.at'], $messageData->getCc()->toHorde()->bare_addresses);
		$this->assertInstanceOf(AddressList::class, $messageData->getBcc());
		$this->assertEquals(['test3@domain.net'], $messageData->getBcc()->toHorde()->bare_addresses);
		$this->assertEquals('Hello', $messageData->getSubject());
		$this->assertEquals('Hi!', $messageData->getBody());
		$this->assertEquals([], $messageData->getAttachments());
	}
}
