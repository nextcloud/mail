<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Model;

use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Model\NewMessageData;
use PHPUnit_Framework_TestCase;

class NewMessageDataTest extends PHPUnit_Framework_TestCase {

	public function testConstructionFromSimpleRequestData() {
		$account = $this->createMock(Account::class);
		$to = '"Test" <test@domain.com>';
		$cc = '';
		$bcc = '';
		$subject = 'Hello';
		$body = 'Hi!';
		$attachments = null;
		$messageData = NewMessageData::fromRequest($account, $to, $cc, $bcc, $subject, $body, $attachments);

		$this->assertEquals($account, $messageData->getAccount());
		$this->assertInstanceOf(AddressList::class, $messageData->getTo());
		$this->assertInstanceOf(AddressList::class, $messageData->getCc());
		$this->assertInstanceOf(AddressList::class, $messageData->getBcc());
		$this->assertEquals('Hello', $messageData->getSubject());
		$this->assertEquals('Hi!', $messageData->getBody());
		$this->assertEquals([], $messageData->getAttachments());
	}

	public function testConstructionFromComplexRequestData() {
		$account = $this->createMock(Account::class);
		$to = '"Test" <test@domain.com>, test2@domain.de';
		$cc = 'test2@domain.at';
		$bcc = '"Test3" <test3@domain.net>';
		$subject = 'Hello';
		$body = 'Hi!';
		$attachments = null;
		$messageData = NewMessageData::fromRequest($account, $to, $cc, $bcc, $subject, $body, $attachments);

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
