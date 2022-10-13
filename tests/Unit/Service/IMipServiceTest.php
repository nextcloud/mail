<?php
/*
 * *
 *  * {$app} App
 *  *
 *  * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gm.net>
 *
 * @author 2022 Anna Larch <anna.larch@gm.net>
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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\IMipService;
use OCA\Mail\Service\MailManager;
use OCP\Calendar\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class IMipServiceTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var MailManager|MockObject  */
	private $mailManager;

	/** @var MockObject|LoggerInterface  */
	private $logger;

	private IMipService $service;

	protected function setUp(): void {
		parent::setUp();

		// iMIP is NC25+
		if (!method_exists(IManager::class, 'handleImipReply')) {
			self::markTestIncomplete();
		}


		$this->accountService = $this->createMock(AccountService::class);
		$this->calendarManager = $this->createMock(IManager::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);

		$this->service = new IMipService(
			$this->accountService,
			$this->calendarManager,
			$this->logger,
			$this->mailboxMapper,
			$this->mailManager,
			$this->messageMapper
		);
	}

	public function testNoSchedulingInformation(): void {
		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('info');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->accountService->expects(self::never())
			->method('findById');
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');
		$this->messageMapper->expects(self::never())
			->method('updateImipData');

		$this->service->process();
	}

	public function testIsSpecialUse(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailbox->setSpecialUse('["sent"]');
		$mailAccount = new MailAccount();
		$mailAccount->setDraftsMailboxId(100);
		$account = new Account($mailAccount);

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with($message->getMailboxId())
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($account);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');

		$this->service->process();
	}

	public function testIsArchive(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailbox->setSpecialUse('["archive"]');
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with($message->getMailboxId())
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($account);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');

		$this->service->process();
	}

	public function testNoSchedulingInfo(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 200,
			'getEmail' => 'dimitrius@stardew-science.com'
		]);
		$imapMessage = $this->createConfiguredMock(IMAPMessage::class, [
			'getUid' => 1
		]);
		$imapMessage->scheduling = [];

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getImapMessagesForScheduleProcessing')
			->with($account, $mailbox, [$message->getUid()])
			->willReturn([$imapMessage]);
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');
		$this->messageMapper->expects(self::once())
			->method('updateImipData')
			->with($message);

		$this->service->process();
	}

	public function testImapConnectionServiceException(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 200,
			'getEmail' => 'dimitrius@stardew-science.com'
		]);
		$imapMessage = $this->createConfiguredMock(IMAPMessage::class, [
			'getUid' => 1
		]);
		$imapMessage->scheduling = [];

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getImapMessagesForScheduleProcessing')
			->willThrowException(new ServiceException());
		$this->logger->expects(self::once())
			->method('error');
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');
		$this->messageMapper->expects(self::never())
			->method('updateImipData');

		$this->service->process();
	}

	public function testIsRequest(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailAccount = new MailAccount();
		$mailAccount->setId(200);
		$mailAccount->setEmail('vincent@stardew-valley.edu');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'REQUEST'];
		$addressList = $this->createMock(AddressList::class);
		$address = $this->createMock(Address::class);

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getImapMessagesForScheduleProcessing')
			->with($account, $mailbox, [$message->getUid()])
			->willReturn([$imapMessage]);
		$imapMessage->expects(self::once())
			->method('getUid')
			->willReturn(1);
		$imapMessage->expects(self::once())
			->method('getFrom')
			->willReturn($addressList);
		$addressList->expects(self::once())
			->method('first')
			->willReturn($address);
		$address->expects(self::once())
			->method('getEmail')
			->willReturn('pam@stardew-bus-company.com');
		$this->logger->expects(self::never())
			->method('info');
		$this->calendarManager->expects(self::never())
			->method('handleIMipReply');
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');
		$this->messageMapper->expects(self::never())
			->method('updateBulk');

		$this->service->process();
	}

	public function testIsReply(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailAccount = new MailAccount();
		$mailAccount->setId(200);
		$mailAccount->setEmail('vincent@stardew-valley.edu');
		$mailAccount->setUserId('vincent');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'REPLY', 'contents' => 'VCARD'];
		$addressList = $this->createMock(AddressList::class);
		$address = $this->createMock(Address::class);

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getImapMessagesForScheduleProcessing')
			->with($account, $mailbox, [$message->getUid()])
			->willReturn([$imapMessage]);
		$imapMessage->expects(self::once())
			->method('getUid')
			->willReturn(1);
		$this->logger->expects(self::never())
			->method('info');
		$imapMessage->expects(self::once())
			->method('getFrom')
			->willReturn($addressList);
		$addressList->expects(self::once())
			->method('first')
			->willReturn($address);
		$address->expects(self::once())
			->method('getEmail')
			->willReturn('pam@stardew-bus-service.com');
		$imapMessage->expects(self::never())
			->method('getInReplyTo')
			->willReturn($addressList);
		$this->calendarManager->expects(self::once())
			->method('handleIMipReply')
			->with('principals/users/vincent',
				'pam@stardew-bus-service.com',
				$account->getEmail(),
				$imapMessage->scheduling[0]['contents']);
		$this->calendarManager->expects(self::never())
			->method('handleIMipCancel');
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

		$this->service->process();
	}

	public function testIsCancel(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailAccount = new MailAccount();
		$mailAccount->setId(200);
		$mailAccount->setEmail('vincent@stardew-valley.edu');
		$mailAccount->setUserId('vincent');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'CANCEL', 'contents' => 'VCARD'];
		$addressList = $this->createMock(AddressList::class);
		$address = $this->createMock(Address::class);

		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([$message]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('findById')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getImapMessagesForScheduleProcessing')
			->with($account, $mailbox, [$message->getUid()])
			->willReturn([$imapMessage]);
		$imapMessage->expects(self::once())
			->method('getUid')
			->willReturn(1);
		$this->logger->expects(self::never())
			->method('info');
		$imapMessage->expects(self::once())
			->method('getFrom')
			->willReturn($addressList);
		$addressList->expects(self::once())
			->method('first')
			->willReturn($address);
		$address->expects(self::once())
			->method('getEmail')
			->willReturn('pam@stardew-bus-service.com');
		$imapMessage->expects(self::once())
			->method('getReplyTo')
			->willReturn(new AddressList([]));
		$this->calendarManager->expects(self::once())
			->method('handleIMipCancel')
			->with('principals/users/vincent',
				'pam@stardew-bus-service.com',
				null,
				$account->getEmail(),
				$imapMessage->scheduling[0]['contents']
			);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

		$this->service->process();
	}
}
