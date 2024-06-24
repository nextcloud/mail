<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Listener\MessageCacheUpdaterListener;
use OCP\EventDispatcher\Event;

class MessageCacheUpdaterListenerTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var MessageCacheUpdaterListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(MessageCacheUpdaterListener::class);
		$this->listener = $this->serviceMock->getService();
	}

	public function testHandleUnrelated() {
		$event = new Event();
		$this->serviceMock->getParameter('mapper')
			->expects($this->never())
			->method('deleteByUid');

		$this->listener->handle($event);
	}

	public function testHandleMessageFlaggedNotCached() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			Tag::LABEL_IMPORTANT,
			true
		);
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('findByUids')
			->with($event->getMailbox(), [123])
			->willReturn([]);
		$this->serviceMock->getParameter('mapper')
			->expects($this->never())
			->method('update');

		$this->listener->handle($event);
	}

	public function testHandleMessageFlagged() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$junk',
			true
		);
		$message = new Message();
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('findByUids')
			->with($event->getMailbox(), [123])
			->willReturn([$message]);
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('update')
			->with($message);

		$this->listener->handle($event);

		$this->assertTrue($message->getFlagJunk());
	}
}
