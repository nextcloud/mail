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

use Horde_Mime_Part;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Model\Message;
use PHPUnit_Framework_TestCase;

class MessageTest extends PHPUnit_Framework_TestCase {

	protected $message;

	protected function setUp() {
		parent::setUp();

		$this->message = new Message();
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
		$from = new AddressList([
			new Address('Fritz', 'fritz@domain.tld'),
		]);

		$this->message->setFrom($from);

		$this->assertSame($from, $this->message->getFrom());
	}

	public function testTo() {
		$expected = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$to = AddressList::parse($expected);

		$this->message->setTo($to);

		$this->assertEquals($to, $this->message->getTo());
	}

	public function testEmptyTo() {
		$this->assertEquals(new AddressList(), $this->message->getTo());
	}

	public function testCC() {
		$raw = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$cc = AddressList::parse($raw);

		$this->message->setCC($cc);

		$this->assertEquals($cc, $this->message->getCC());
	}

	public function testEmptyCC() {
		$this->assertEquals(new AddressList(), $this->message->getCC());
	}

	public function testBCC() {
		$raw = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$bcc = AddressList::parse($raw);

		$this->message->setBCC($bcc);

		$this->assertEquals($bcc, $this->message->getBCC());
	}

	public function testEmptyBCC() {
		$this->assertEquals(new AddressList(), $this->message->getBCC());
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
		$actual = $this->message->getCloudAttachments();

		$this->assertCount(1, $actual);
		//$this->assertEquals($expected, $actual[0]);
	}

}
