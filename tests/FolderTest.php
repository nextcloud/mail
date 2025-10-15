<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\Folder;
use PHPUnit_Framework_MockObject_MockObject;

class FolderTest extends TestCase {
	/** @var int */
	private $accountId;

	/** @var Horde_Imap_Client_Mailbox|PHPUnit_Framework_MockObject_MockObject */
	private $mailbox;

	/** @var Folder */
	private $folder;

	private function mockFolder(array $attributes = [], $delimiter = '.') {
		$this->accountId = 15;
		$this->mailbox = $this->createMock(Horde_Imap_Client_Mailbox::class);

		$this->folder = new Folder($this->accountId, $this->mailbox, $attributes, $delimiter, []);
	}

	public function testGetMailbox() {
		$this->mockFolder();
		$this->mailbox->expects($this->once())
			->method('__get')
			->with($this->equalTo('utf8'))
			->willReturn('Sent');

		$this->assertSame('Sent', $this->folder->getMailbox());
	}

	public function testGetDelimiter() {
		$this->mockFolder([], ',');

		$this->assertSame(',', $this->folder->getDelimiter());
	}

	public function testGetDelimiterNull(): void {
		$this->mockFolder([], null);

		$this->assertNull($this->folder->getDelimiter());
	}

	public function testGetAttributes() {
		$this->mockFolder(['\noselect']);

		$this->assertSame(['\noselect'], $this->folder->getAttributes());
	}

	public function testSetStatus() {
		$this->mockFolder();

		$this->folder->setStatus([
			'unseen' => 4,
		]);

		$this->addToAssertionCount(1);
	}

	public function testSpecialUse() {
		$this->mockFolder();

		$this->folder->addSpecialUse('flagged');

		$this->assertCount(1, $this->folder->getSpecialUse());
		$this->assertSame('flagged', $this->folder->getSpecialUse()[0]);
	}
}
