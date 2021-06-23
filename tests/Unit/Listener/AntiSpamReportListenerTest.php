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
use OCA\Mail\Db\Tag;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Listener\AntiSpamReportListener;
use OCP\EventDispatcher\Event;

class AntiSpamReportListenerTest extends TestCase {

	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var AntiSpamReportListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(AntiSpamReportListener::class);
		$this->listener = $this->serviceMock->getService();
	}

	public function testHandleUnrelated() {
		$event = new Event();

		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('getReportEmail');
		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('sendSpamReport');
		$this->serviceMock->getParameter('logger')
			->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleNotFlaggedJunk() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			Tag::LABEL_IMPORTANT,
			true
		);

		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('getReportEmail');
		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('sendSpamReport');
		$this->serviceMock->getParameter('logger')
			->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleNotUnflaggingJunk() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'junk',
			false
		);

		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('getReportEmail');
		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('sendSpamReport');
		$this->serviceMock->getParameter('logger')
			->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleNoReportEmailSet() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'junk',
			true
		);

		$this->serviceMock->getParameter('service')
			->expects($this->once())
			->method('getReportEmail')
			->willReturn('');
		$this->serviceMock->getParameter('service')
			->expects($this->never())
			->method('sendSpamReport');
		$this->serviceMock->getParameter('logger')
			->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleJunkExceptionOnSend() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'junk',
			true
		);

		$this->serviceMock->getParameter('service')
			->expects($this->once())
			->method('getReportEmail')
			->willReturn('SpammitySpam@WonderfulSpam.egg');
		$this->serviceMock->getParameter('service')
			->expects($this->once())
			->method('sendSpamReport')
			->with($account, $mailbox, 123)
			->willThrowException(new ServiceException());
		$this->serviceMock->getParameter('logger')
			->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandle() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'junk',
			true
		);

		$this->serviceMock->getParameter('service')
			->expects($this->once())
			->method('getReportEmail')
			->willReturn('SpammitySpam@WonderfulSpam.egg');
		$this->serviceMock->getParameter('service')
			->expects($this->once())
			->method('sendSpamReport')
			->with($account, $mailbox, 123);
		$this->serviceMock->getParameter('logger')
			->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}
}
