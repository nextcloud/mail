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
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CopySendMessageHandlerTest extends TestCase {
	private IMAPClientFactory|MockObject $imapClientFactory;
	private MailboxMapper|MockObject $mailboxMapper;
	private LoggerInterface|MockObject $loggerInterface;
	private MockObject|MessageMapper $messageMapper;
	private MockObject|FlagRepliedMessageHandler $flagRepliedMessageHandler;
	private CopySentMessageHandler $handler;

	protected function setUp(): void {

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->flagRepliedMessageHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->handler = new CopySentMessageHandler(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->loggerInterface,
			$this->messageMapper,
		);
		$this->handler->setNext($this->flagRepliedMessageHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus','setStatus', 'getRaw']);
		$mock = $localMessage->getMock();
		$mailbox = new Mailbox();
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$mock->expects(self::once())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_RAW);
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->loggerInterface->expects(self::never())
			->method('error');
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$mock->expects(self::once())
			->method('getRaw')
			->willReturn('Test');
		$this->messageMapper->expects(self::once())
			->method('save');
		$mock->expects(self::once())
			->method('setStatus')
			->willReturn(LocalMessage::STATUS_PROCESSED);
		$this->flagRepliedMessageHandler->expects(self::once())
			->method('process')
			->with($account, $mock);


		$this->handler->process($account, $mock);
	}

	public function testProcessNoSentMailbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus','setStatus']);
		$mock = $localMessage->getMock();

		$this->loggerInterface->expects(self::once())
			->method('warning');
		$mock->expects(self::once())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_RAW);
		$mock->expects(self::once())
			->method('setStatus')
			->with(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$this->loggerInterface->expects(self::never())
			->method('error');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->messageMapper->expects(self::never())
			->method('save');
		$this->flagRepliedMessageHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock);
	}

	public function testProcessNoSentMailboxFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setSentMailboxId(1);
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus','setStatus']);
		$mock = $localMessage->getMock();

		$this->loggerInterface->expects(self::never())
			->method('warning');
		$mock->expects(self::once())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_RAW);
		$mock->expects(self::once())
			->method('setStatus')
			->with(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willThrowException(new DoesNotExistException(''));
		$this->loggerInterface->expects(self::once())
			->method('error');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->messageMapper->expects(self::never())
			->method('save');
		$this->flagRepliedMessageHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock);
	}

	public function testProcessCouldNotCopy(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus','setStatus', 'getRaw']);
		$mock = $localMessage->getMock();
		$mailbox = new Mailbox();
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$mock->expects(self::once())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_RAW);
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$mock->expects(self::once())
			->method('getRaw')
			->willReturn('123 Content');
		$this->messageMapper->expects(self::once())
			->method('save')
			->willThrowException(new Horde_Imap_Client_Exception());
		$mock->expects(self::once())
			->method('setStatus')
			->with(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$this->loggerInterface->expects(self::once())
			->method('error');
		$this->flagRepliedMessageHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock);
	}

	public function testProcessAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus']);
		$mock = $localMessage->getMock();

		$this->loggerInterface->expects(self::never())
			->method('warning');
		$mock->expects(self::once())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_PROCESSED);
		$this->loggerInterface->expects(self::never())
			->method('error');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->messageMapper->expects(self::never())
			->method('save');
		$this->flagRepliedMessageHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $mock);
	}
}
