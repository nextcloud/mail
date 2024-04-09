<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Unit\Send;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\SentMailboxHandler;
use PHPUnit\Framework\MockObject\MockObject;

class SentMailboxHandlerTest extends TestCase {
	private AntiAbuseHandler|MockObject $antiAbuseHandler;
	private SentMailboxHandler $handler;

	protected function setUp(): void {
		$this->antiAbuseHandler = $this->createMock(AntiAbuseHandler::class);
		$this->handler = new SentMailboxHandler();
		$this->handler->setNext($this->antiAbuseHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setSentMailboxId(1);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->antiAbuseHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage);
	}

	public function testNoSentMailbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['setStatus']);
		$mock = $localMessage->getMock();

		$mock->expects(self::once())
			->method('setStatus')
			->with(LocalMessage::STATUS_NO_SENT_MAILBOX);
		$this->antiAbuseHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock);
	}
}
