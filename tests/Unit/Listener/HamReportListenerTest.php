<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Tag;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Listener\HamReportListener;
use OCA\Mail\Listener\SpamReportListener;
use OCP\EventDispatcher\Event;

class HamReportListenerTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SpamReportListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(HamReportListener::class);
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

	public function testHandleNotUnflaggingNonjunk(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$notjunk',
			false
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::never())
			->method('getHamEmail');
		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::never())
			->method('sendReportEmail');
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleJunkExceptionOnSend() : void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			123,
			'$notjunk',
			true
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::once())
			->method('sendReportEmail')
			->with($account, $mailbox, 123, '$notjunk')
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
			'$notjunk',
			true
		);

		$this->serviceMock->getParameter('antiSpamService')
			->expects(self::once())
			->method('sendReportEmail')
			->with($account, $mailbox, 123, '$notjunk');
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('error');

		$this->listener->handle($event);
	}
}
