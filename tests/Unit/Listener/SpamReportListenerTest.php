<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author Anna Larch <anna@nextcloud.com>
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
use OCA\Mail\Listener\SpamReportListener;
use OCP\EventDispatcher\Event;

class SpamReportListenerTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SpamReportListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(SpamReportListener::class);
		$this->listener = $this->serviceMock->getService();
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::never())
			->method('sendReportEmail');
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleNotFlaggedJunk(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			Tag::LABEL_IMPORTANT,
			true
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::never())
			->method('sendReportEmail');
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleNotUnflaggingJunk(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$junk',
			false
		);

		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleJunkExceptionOnSend(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$junk',
			true
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::once())
			->method('sendReportEmail')
			->with($account, $mailbox, 123, '$junk')
			->willThrowException(new ServiceException());
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandle(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$junk',
			true
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::once())
			->method('sendReportEmail')
			->with($account, $mailbox, 123, '$junk');
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}
}
