<?php

namespace OCA\Mail\Tests\Model;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
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
		$actual = Message::parseAddressList($list);

		$this->assertEquals($expected, $actual);
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
		$to = [
			'alice@example.com',
			'bob@example.com',
		];

		$this->message->setTo($to);

		$this->assertSame($to, $this->message->getToList());
		$this->assertEquals($to[0], $this->message->getTo());
	}

	public function testEmptyTo() {
		$this->assertNull($this->message->getTo());
		$this->assertEquals([], $this->message->getToList());
	}

	public function testCC() {
		$cc = [
			'alice@example.com',
			'bob@example.com',
		];

		$this->message->setCC($cc);

		$this->assertSame($cc, $this->message->getCCList());
	}

	public function testEmptyCC() {
		$this->assertEquals([], $this->message->getCCList());
	}

	public function testBCC() {
		$bcc = [
			'alice@example.com',
			'bob@example.com',
		];

		$this->message->setBCC($bcc);

		$this->assertSame($bcc, $this->message->getBCCList());
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
