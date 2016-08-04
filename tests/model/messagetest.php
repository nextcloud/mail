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

use Test\TestCase;
use Horde_Mime_Part;
use OCA\Mail\Model\Message;

class MessageTest extends TestCase {

	protected $message;

	protected function setUp() {
		parent::setUp();

		$this->message = new Message();
	}

	public function addressListDataProvider() {
		return [
			[
				// simple address
				'user@example.com',
				[
					'user@example.com'
				]
			],
			[
				// address list with comma as delimiter
				'user@example.com, anotheruser@example.com',
				[
					'user@example.com',
					'anotheruser@example.com'
				]
			],
			[
				// empty list
				'',
				[]
			],
			[
				// address with name
				'"user" <user@example.com>',
				[
					'user@example.com'
				]
			],
			[
				// Trailing slash
				'"user" <user@example.com>,',
				[
					'user@example.com'
				]
			]
		];
	}

	/**
	 * @dataProvider addressListDataProvider
	 */
	public function testParseAddressList($list, $expected) {
		$result = Message::parseAddressList($list);

		foreach ($expected as $exp) {
			$this->assertTrue($result->contains($exp));
		}
	}

	public function testFlags() {
		$flags = [
			'seen',
			'flagged',
		];

		$this->message->setFlags($flags);

		$this->assertSame($flags, $this->message->getFlags());
	}

	public function testFrom() {
		$from = 'user@example.com';

		$this->message->setFrom($from);

		$this->assertSame($from, $this->message->getFrom());
	}

	public function testTo() {
		$expected = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$to = Message::parseAddressList($expected);

		$this->message->setTo($to);

		$this->assertEquals($expected, $this->message->getToList());
		$this->assertEquals($to[0], $this->message->getTo());
	}

	public function testEmptyTo() {
		$this->assertNull($this->message->getTo());
		$this->assertEquals([], $this->message->getToList());
	}

	public function testCC() {
		$expected = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$cc = Message::parseAddressList($expected);

		$this->message->setCC($cc);

		$this->assertEquals($expected, $this->message->getCCList());
	}

	public function testEmptyCC() {
		$this->assertEquals([], $this->message->getCCList());
	}

	public function testBCC() {
		$expected = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$bcc = Message::parseAddressList($expected);

		$this->message->setBCC($bcc);

		$this->assertEquals($expected, $this->message->getBCCList());
	}

	public function testEmptyBCC() {
		$this->assertEquals([], $this->message->getBCCList());
	}

	public function testRepliedMessage() {
		$reply = new Message();

		$this->message->setRepliedMessage($reply);
		$actual = $this->message->getRepliedMessage();

		$this->assertSame($reply, $actual);
	}

	public function testSubject() {
		$subject = 'test message';

		$this->message->setSubject($subject);

		$this->assertSame($subject, $this->message->getSubject());
	}

	public function testEmptySubject() {
		$this->assertSame('', $this->message->getSubject());
	}

	public function testContent() {
		$content = "hello!";

		$this->message->setContent($content);

		$this->assertSame($content, $this->message->getContent());
	}

	public function testAttachments() {
		$name = 'coffee.jpg';
		$mimeType = 'image/jpeg';
		$contents = 'file content';

		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$file->expects($this->once())
			->method('getName')
			->will($this->returnValue($name));
		$file->expects($this->once())
			->method('getContent')
			->will($this->returnValue($contents));
		$file->expects($this->once())
			->method('getMimeType')
			->will($this->returnValue($mimeType));

		$expected = new Horde_Mime_Part();
		$expected->setCharset('us-ascii');
		$expected->setDisposition('attachment');
		$expected->setName($name);
		$expected->setContents($contents);
		$expected->setType($mimeType);

		$this->message->addAttachmentFromFiles($file);
		$actual = $this->message->getAttachments();

		$this->assertCount(1, $actual);
		//$this->assertEquals($expected, $actual[0]);
	}

}
