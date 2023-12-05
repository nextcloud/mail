<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2021 Anna Larch <anna.larch@gmx.net>
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
use OCA\Mail\Db\Message;

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
}
