<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Mail;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Listener\AddressCollectionListener;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AddressCollectionListenerTest extends TestCase {
	/** @var IUserPreferences|MockObject */
	private $preferences;

	/** @var AddressCollector|MockObject */
	private $addressCollector;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->preferences = $this->createMock(IUserPreferences::class);
		$this->addressCollector = $this->createMock(AddressCollector::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new AddressCollectionListener(
			$this->preferences,
			$this->addressCollector,
			$this->logger
		);
	}

	public function testHandleUnrelated() {
		$event = new Event();
		$this->addressCollector->expects($this->never())
			->method('addAddresses');
		$this->logger->expects($this->never())->method($this->anything());

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleOptOut() {
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => 'test'
		]);
		$event = $this->createConfiguredMock(MessageSentEvent::class, [
			'getAccount' => $account
		]);
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('test', 'collect-data', 'true')
			->willReturn('false');
		$this->addressCollector->expects($this->never())
			->method('addAddresses');

		$this->listener->handle($event);
	}

	public function testHandle() {
		/** @var Account|MockObject $account */
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => 'test'
		]);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var RepliedMessageData|MockObject $repliedMessageData */
		$repliedMessageData = $this->createMock(RepliedMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('test', 'collect-data', 'true')
			->willReturn('true');
		/** @var Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(Horde_Mime_Mail::class);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			'abc123',
			null,
			$message,
			$mail
		);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList([Address::fromRaw('to', 'to@email')]));
		$message->expects($this->once())
			->method('getCC')
			->willReturn(new AddressList([Address::fromRaw('cc', 'cc@email')]));
		$message->expects($this->once())
			->method('getBCC')
			->willReturn(new AddressList([Address::fromRaw('bcc', 'bcc@email')]));
		$this->addressCollector->expects($this->once())
			->method('addAddresses')
			->with(
				'test',
				$this->equalTo(new AddressList([
					Address::fromRaw('to', 'to@email'),
					Address::fromRaw('cc', 'cc@email'),
					Address::fromRaw('bcc', 'bcc@email'),
				]))
			);
		$this->logger->expects($this->never())->method($this->anything());

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}
}
