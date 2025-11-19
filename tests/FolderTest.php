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
	private ?int $accountId = null;

	/** @var Horde_Imap_Client_Mailbox|PHPUnit_Framework_MockObject_MockObject */
	private $mailbox;

	private ?\OCA\Mail\Folder $folder = null;

	private function mockFolder(array $attributes = [], ?string $delimiter = '.'): void {
		$this->accountId = 15;
		$this->mailbox = $this->createMock(Horde_Imap_Client_Mailbox::class);

		$this->folder = new Folder($this->accountId, $this->mailbox, $attributes, $delimiter, []);
	}

	public function testGetMailbox(): void {
		$this->mockFolder();
		$this->mailbox->expects($this->once())
			->method('__get')
			->with($this->equalTo('utf8'))
			->willReturn('Sent');

		$this->assertSame('Sent', $this->folder->getMailbox());
	}

	public function testGetDelimiter(): void {
		$this->mockFolder([], ',');

		$this->assertSame(',', $this->folder->getDelimiter());
	}

	public function testGetDelimiterNull(): void {
		$this->mockFolder([], null);

		$this->assertNull($this->folder->getDelimiter());
	}

	public function testGetAttributes(): void {
		$this->mockFolder(['\noselect']);

		$this->assertSame(['\noselect'], $this->folder->getAttributes());
	}

	public function testSetStatus(): void {
		$this->mockFolder();

		$this->folder->setStatus([
			'unseen' => 4,
		]);

		$this->addToAssertionCount(1);
	}

	public function testSpecialUse(): void {
		$this->mockFolder();

		$this->folder->addSpecialUse('flagged');

		$this->assertCount(1, $this->folder->getSpecialUse());
		$this->assertSame('flagged', $this->folder->getSpecialUse()[0]);
	}
}
