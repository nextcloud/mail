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
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Listener\MessageDeletedCacheUpdaterListener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class MessageDeletedCacheUpdaterListenerTest extends TestCase {

	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var IEventListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(MessageDeletedCacheUpdaterListener::class);
		$this->listener = $this->serviceMock->getService();
	}

	public function testHandleUnrelated() {
		$event = new Event();
		$this->serviceMock->getParameter('mapper')
			->expects($this->never())
			->method('deleteByUid');

		$this->listener->handle($event);
	}

	public function testHandle() {
		$event = $this->createMock(MessageDeletedEvent::class);
		$this->serviceMock->getParameter('mapper')
			->expects($this->once())
			->method('deleteByUid')
			->with($event->getMailbox(), $event->getMessageId());

		$this->listener->handle($event);
	}

}
