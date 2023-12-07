<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
