<?php
/*
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
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
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use PHPUnit\Framework\MockObject\MockObject;

class SendHandlerTest extends TestCase {
	private MockObject|IMailTransmission $transmission;
	private MockObject|CopySentMessageHandler $copySentMessageHandler;
	private MockObject|FlagRepliedMessageHandler $flagRepliedMessageHandler;
	private SendHandler $handler;

	protected function setUp(): void {
		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->copySentMessageHandler = $this->createMock(CopySentMessageHandler::class);
		$this->flagRepliedMessageHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->handler = new SendHandler($this->transmission);
		$this->handler->setNext($this->copySentMessageHandler)
			->setNext($this->flagRepliedMessageHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($account, $localMessage);
		$this->copySentMessageHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);

		$this->transmission->expects(self::never())
			->method('sendMessage');
		$this->copySentMessageHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessError(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus']);
		$mock = $localMessage->getMock();
		$mock->expects(self::exactly(3))
			->method('getStatus')
			->willReturnOnConsecutiveCalls([
				LocalMessage::STATUS_RAW,
				LocalMessage::STATUS_RAW,
				LocalMessage::STATUS_SMPT_SEND_FAIL,
			]);

		$this->transmission->expects(self::once())
			->method('sendMessage');
		$this->copySentMessageHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock);
	}
}
