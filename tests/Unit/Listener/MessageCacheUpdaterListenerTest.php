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

	public function testHandleMessageFlagged() {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$message = new Message();
		$message->setUid(123);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			$message,
			'$junk',
			true
		);
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('update')
			->with($message);

		$this->listener->handle($event);

		$this->assertTrue($message->getFlagJunk());
	}

	public function testHandleMessageFlaggedTag() {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$message = new Message();
		$message->setUid(123);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			$message,
			Tag::LABEL_IMPORTANT,
			true
		);
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('update')
			->with($message);

		$this->listener->handle($event);

		$this->assertTrue($message->getFlagImportant());
	}
}
