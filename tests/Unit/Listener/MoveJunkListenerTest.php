<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Listener\MoveJunkListener;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class MoveJunkListenerTest extends TestCase {
	private IMailManager $mailManager;
	private LoggerInterface $logger;
	private MoveJunkListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mailManager = $this->createMock(IMailManager::class);
		$this->logger = new TestLogger();

		$this->listener = new MoveJunkListener(
			$this->mailManager,
			$this->logger
		);
	}

	public function testIgnoreOtherFlags(): void {
		$event = $this->createMock(MessageFlaggedEvent::class);
		$event->method('getFlag')
			->willReturn('test');

		$event->expects($this->never())
			->method('getAccount');

		$this->listener->handle($event);
	}

	public function testMoveJunkDisabled(): void {
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);

		$event = $this->createMock(MessageFlaggedEvent::class);
		$event->method('getFlag')
			->willReturn('$junk');
		$event->method('getAccount')
			->willReturn($account);

		$event->expects($this->never())
			->method('getMailbox');

		$this->listener->handle($event);
	}

	public function testMoveJunkMailboxNotFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setJunkMailboxId(200);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(100);

		$this->mailManager->method('getMailbox')
			->willThrowException(new ClientException('Computer says no'));

		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			100,
			'$junk',
			true
		);

		$this->listener->handle($event);

		$this->assertCount(1, $this->logger->records);
	}

	public function testMoveJunkAlreadyInJunk(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setJunkMailboxId(200);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(200);

		$this->mailManager->expects($this->never())
			->method('moveMessage');

		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			100,
			'$junk',
			true
		);

		$this->listener->handle($event);
	}

	public function testMoveJunkFailed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setJunkMailboxId(200);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setName('INBOX');

		$junkMailbox = new Mailbox();
		$junkMailbox->setId(200);
		$junkMailbox->setName('Junk');

		$this->mailManager->method('getMailbox')
			->willReturn($junkMailbox);

		$this->mailManager->method('moveMessage')
			->willThrowException(new ServiceException('Computer says no'));

		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			100,
			'$junk',
			true
		);

		$this->listener->handle($event);

		$this->assertCount(1, $this->logger->records);
	}

	public function testMoveJunkAlreadyInInbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setJunkMailboxId(200);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setName('INBOX');

		$this->mailManager->expects($this->never())
			->method('moveMessage');

		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			100,
			'$junk',
			false
		);

		$this->listener->handle($event);
	}

	public function testMoveJunkToInboxFailed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setJunkMailboxId(200);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setName('Junk');

		$junkMailbox = new Mailbox();
		$junkMailbox->setId(200);
		$junkMailbox->setName('Junk');

		$this->mailManager->method('getMailbox')
			->willReturn($junkMailbox);

		$this->mailManager->method('moveMessage')
			->willThrowException(new ServiceException('Computer says no'));

		$event = new MessageFlaggedEvent(
			$account,
			$mailbox,
			100,
			'$junk',
			false
		);

		$this->listener->handle($event);

		$this->assertCount(1, $this->logger->records);
	}
}
