<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Model;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Part;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Model\Message;

class MessageTest extends TestCase {
	protected $message;

	protected function setUp(): void {
		parent::setUp();

		$this->message = new Message();
	}

	public function testFlags(): void {
		$flags = [
			'seen',
			'flagged',
		];

		$this->message->setFlags($flags);

		$this->assertSame($flags, $this->message->getFlags());
	}

	public function testFrom(): void {
		$from = new AddressList([
			Address::fromRaw('Fritz', 'fritz@domain.tld'),
		]);

		$this->message->setFrom($from);

		$this->assertSame($from, $this->message->getFrom());
	}

	public function testTo(): void {
		$expected = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$to = AddressList::parse($expected);

		$this->message->setTo($to);

		$this->assertEquals($to, $this->message->getTo());
	}

	public function testEmptyTo(): void {
		$this->assertEquals(new AddressList(), $this->message->getTo());
	}

	public function testCC(): void {
		$raw = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$cc = AddressList::parse($raw);

		$this->message->setCC($cc);

		$this->assertEquals($cc, $this->message->getCC());
	}

	public function testEmptyCC(): void {
		$this->assertEquals(new AddressList(), $this->message->getCC());
	}

	public function testBCC(): void {
		$raw = [
			'alice@example.com',
			'Bob <bob@example.com>',
		];
		$bcc = AddressList::parse($raw);

		$this->message->setBCC($bcc);

		$this->assertEquals($bcc, $this->message->getBCC());
	}

	public function testEmptyBCC(): void {
		$this->assertEquals(new AddressList(), $this->message->getBCC());
	}

	public function testRepliedMessage(): void {
		$reply = '9609171955.AA24342@cmstex2.maths.umanitoba.ca';

		$this->message->setInReplyTo($reply);
		$actual = $this->message->getInReplyTo();

		$this->assertSame($reply, $actual);
	}

	public function testSubject(): void {
		$subject = 'test message';

		$this->message->setSubject($subject);

		$this->assertSame($subject, $this->message->getSubject());
	}

	public function testEmptySubject(): void {
		$this->assertSame('', $this->message->getSubject());
	}

	public function testContent(): void {
		$content = 'hello!';

		$this->message->setContent($content);

		$this->assertSame($content, $this->message->getContent());
	}

	public function testAttachments(): void {
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
	}
}
