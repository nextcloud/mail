<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageRetention;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Listener\MessageKnownSinceListener;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class MessageKnownSinceListenerTest extends TestCase {

	/** @var MessageRetentionMapper|MockObject */
	private $messageRetentionMapper;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	private MessageKnownSinceListener $messageKnownSinceListener;

	protected function setUp(): void {
		parent::setUp();

		$this->messageRetentionMapper = $this->createMock(MessageRetentionMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->messageKnownSinceListener = new MessageKnownSinceListener(
			$this->messageRetentionMapper,
			$this->timeFactory,
		);
	}

	public function testHandle(): void {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(60);
		$dbAccount->setTrashMailboxId(420);
		$account = new Account($dbAccount);
		$mailbox = $this->createMock(Mailbox::class);
		$message1 = new Message();
		$message1->setUid(11);
		$message1->setMailboxId(420);
		$message2 = new Message();
		$message2->setUid(12);
		$message2->setMailboxId(1);
		$messages = [$message1, $message2];
		$event = new NewMessagesSynchronized($account, $mailbox, $messages);

		$messageRetention = new MessageRetention();
		$messageRetention->setUid(11);
		$messageRetention->setMailboxId(420);
		$messageRetention->setKnownSince(1000);

		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn(1000);
		$this->messageRetentionMapper->expects($this->once())
			->method('insert')
			->with($messageRetention);

		$this->messageKnownSinceListener->handle($event);
	}

	public function testHandleWithoutRetention(): void {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(null);
		$dbAccount->setTrashMailboxId(420);
		$account = new Account($dbAccount);
		$mailbox = $this->createMock(Mailbox::class);
		$message1 = new Message();
		$message1->setMessageId('<foobar@local.host>');
		$message1->setMailboxId(420);
		$message2 = new Message();
		$message2->setMessageId('<foobar2@local.host>');
		$message2->setMailboxId(1);
		$messages = [$message1, $message2];
		$event = new NewMessagesSynchronized($account, $mailbox, $messages);

		$this->timeFactory->expects($this->never())
			->method('getTime');
		$this->messageRetentionMapper->expects($this->never())
			->method('insert');

		$this->messageKnownSinceListener->handle($event);
	}
}
