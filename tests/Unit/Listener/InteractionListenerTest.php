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
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Listener\InteractionListener;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use function class_exists;

class InteractionListenerTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var InteractionListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(InteractionListener::class);

		$this->listener = $this->serviceMock->getService();
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandle(): void {
		if (!class_exists(ContactInteractedWithEvent::class)) {
			$this->markTestSkipped(ContactInteractedWithEvent::class . ' does not exist');
			return;
		}

		$message = new LocalMessage();
		$message->setRecipients([
			Recipient::fromParams([
				'label' => 'rec 1',
				'email' => 'u1@domain.tld',
				'type' => Recipient::TYPE_TO,
			]),
			Recipient::fromParams([
				'label' => 'rec 1',
				'email' => 'u2@domain.tld',
				'type' => Recipient::TYPE_TO,
			]),
			Recipient::fromParams([
				'label' => 'rec 1',
				'email' => 'u3@domain.tld',
				'type' => Recipient::TYPE_CC,
			]),
			Recipient::fromParams([
				'label' => 'rec 1',
				'email' => 'u4@domain.tld',
				'type' => Recipient::TYPE_BCC,
			]),
			Recipient::fromParams([
				'label' => 'rec 1',
				'email' => 'u2@domain.tld',
				'type' => Recipient::TYPE_CC,
			]),
		]);
		$event = new MessageSentEvent(
			$this->createMock(Account::class),
			$message,
		);
		$user = $this->createMock(IUser::class);
		$this->serviceMock->getParameter('userSession')
			->method('getUser')
			->willReturn($user);
		$this->serviceMock->getParameter('dispatcher')
			->expects($this->exactly(4))
			->method('dispatch');

		$this->listener->handle($event);
	}
}
