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
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Listener\InteractionListener;
use OCA\Mail\Model\IMessage;
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

		$to = new AddressList([
			Address::fromRaw('rec 1', 'u1@domain.tld'),
			Address::fromRaw('rec 1', 'u2@domain.tld'),
		]);
		$cc = new AddressList([
			Address::fromRaw('rec 1', 'u3@domain.tld'),
		]);
		$bcc = new AddressList([
			Address::fromRaw('rec 1', 'u4@domain.tld'),
			Address::fromRaw('rec 1', 'u2@domain.tld'), // intentional duplicate
		]);
		$event = $this->createMock(MessageSentEvent::class);
		$message = $this->createMock(IMessage::class);
		$event
			->method('getMessage')
			->willReturn($message);
		$message
			->method('getTo')
			->willReturn($to);
		$message
			->method('getCC')
			->willReturn($cc);
		$message
			->method('getBCC')
			->willReturn($bcc);
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
