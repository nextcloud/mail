<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\IMipService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Util\ServerVersion;
use OCP\Calendar\IManager;
use OCP\ServerVersion as OCPServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class IMipServiceTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var AccountService|MockObject */
	private $accountService;

	private IManager $calendarManager;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IMailTransmission|MockObject */
	private $mailTransmission;

	/** @var ImapMessageMapper|MockObject */
	private $imapMessageMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var AttachmentService|MockObject */
	private $attachmentService;

	private IMipService $service;

	private ServerVersion|MockObject $serverVersion;

	private OCPServerVersion $OCPServerVersion;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->calendarManager = $this->createMock(IManager::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->OCPServerVersion = new OCPServerVersion();
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->imapMessageMapper = $this->createMock(ImapMessageMapper::class);
		$this->mailTransmission = $this->createMock(IMailTransmission::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);

		$this->service = new IMipService(
			$this->accountService,
			$this->calendarManager,
			$this->logger,
			$this->mailboxMapper,
			$this->mailManager,
			$this->messageMapper,
			$this->serverVersion,
			$this->imapClientFactory,
			$this->imapMessageMapper,
			$this->mailTransmission,
			$this->attachmentService
		);
	}

	public function testNoSchedulingInformation(): void {
		$this->messageMapper->expects(self::once())
			->method('findIMipMessagesAscending')
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('debug');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->accountService->expects(self::never())
			->method('findById');
		$this->calendarManager->expects(self::never())
			->method('handleIMipRequest');
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
			->method('handleIMipRequest');
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
			->method('handleIMipRequest');
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
			->method('handleIMipRequest');
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
			->method('handleIMipRequest');
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
		$mailAccount->setUserId('vincent');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'REQUEST', 'contents' => 'VCALENDAR'];
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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(32);
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
			->willReturn('pam@stardew-bus-service.com');
		$this->logger->expects(self::never())
			->method('info');
		$this->calendarManager->expects(self::once())
			->method('handleIMipRequest')
			->with('principals/users/vincent',
				'pam@stardew-bus-service.com',
				$account->getEmail(),
				$imapMessage->scheduling[0]['contents']);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(32);
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
			->willReturn('pam@stardew-bus-service.com');
		$this->calendarManager->expects(self::once())
			->method('handleIMipReply')
			->with('principals/users/vincent',
				'pam@stardew-bus-service.com',
				$account->getEmail(),
				$imapMessage->scheduling[0]['contents']);
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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(32);
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

	public function testIsRequestServerVersion33(): void {
		if ($this->OCPServerVersion->getMajorVersion() < 33) {
			$this->markTestSkipped('Requires Nextcloud 33 or higher');
		}

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
		$imapMessage->scheduling[] = ['method' => 'REQUEST', 'contents' => 'VCALENDAR'];
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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(33);
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
			->willReturn('pam@stardew-bus-service.com');
		$this->logger->expects(self::never())
			->method('info');
		$this->calendarManager->expects(self::once())
			->method('handleIMip')
			->with('vincent', 'VCALENDAR', [
				'recipient' => 'vincent@stardew-valley.edu',
				'absent' => 'ignore',
				'absentCreateStatus' => 'tentative',
			])
			->willReturn(true);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

		$this->service->process();
	}

	public function testIsReplyServerVersion33(): void {
		if ($this->OCPServerVersion->getMajorVersion() < 33) {
			$this->markTestSkipped('Requires Nextcloud 33 or higher');
		}

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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(33);
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
		$this->calendarManager->expects(self::once())
			->method('handleIMip')
			->with('vincent', 'VCARD', [
				'recipient' => 'vincent@stardew-valley.edu',
				'absent' => 'ignore',
				'absentCreateStatus' => 'tentative'
			])
			->willReturn(true);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

		$this->service->process();
	}

	public function testIsCancelServerVersion33(): void {
		if ($this->OCPServerVersion->getMajorVersion() < 33) {
			$this->markTestSkipped('Requires Nextcloud 33 or higher');
		}

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
		$this->serverVersion->expects(self::once())
			->method('getMajorVersion')
			->willReturn(33);
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
		$this->calendarManager->expects(self::once())
			->method('handleIMip')
			->with('vincent', 'VCARD', [
				'recipient' => 'vincent@stardew-valley.edu',
				'absent' => 'ignore',
				'absentCreateStatus' => 'tentative'
			])
			->willReturn(true);
		$this->messageMapper->expects(self::once())
			->method('updateImipData');

		$this->service->process();
	}

	public function testHandleImipRequestThrowsException(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailbox->setName('INBOX');
		$mailAccount = new MailAccount();
		$mailAccount->setId(200);
		$mailAccount->setEmail('vincent@stardew-valley.edu');
		$mailAccount->setUserId('vincent');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'REQUEST', 'contents' => 'VCALENDAR'];
		$addressList = $this->createMock(AddressList::class);
		$address = $this->createMock(Address::class);

		// Create a mock IMAP client that will be returned by the factory
		$imapClient = $this->createMock(\Horde_Imap_Client_Socket::class);

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
			->willReturn('pam@stardew-bus-service.com');
		$this->calendarManager->expects(self::once())
			->method('handleIMipRequest')
			->willThrowException(new \Exception('Calendar error'));
		$this->logger->expects(self::exactly(2))
			->method('error');
		$this->messageMapper->expects(self::once())
			->method('updateImipData')
			->with(self::callback(fn (Message $msg) => $msg->isImipProcessed() === true && $msg->isImipError() === true));

		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($imapClient);

		// The error notification should fail to send due to missing raw message
		$this->imapMessageMapper->expects(self::once())
			->method('getFullText')
			->willReturn(null);

		$imapClient->expects(self::once())
			->method('logout');

		$this->service->process();
	}

	public function testHandleImipRequestThrowsExceptionAndSendsNotification(): void {
		$message = new Message();
		$message->setImipMessage(true);
		$message->setUid(1);
		$message->setMailboxId(100);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setAccountId(200);
		$mailbox->setName('INBOX');
		$mailAccount = new MailAccount();
		$mailAccount->setId(200);
		$mailAccount->setEmail('vincent@stardew-valley.edu');
		$mailAccount->setUserId('vincent');
		$account = new Account($mailAccount);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->scheduling[] = ['method' => 'REQUEST', 'contents' => 'VCALENDAR'];
		$addressList = $this->createMock(AddressList::class);
		$address = $this->createMock(Address::class);

		// Create a mock IMAP client that will be returned by the factory
		$imapClient = $this->createMock(\Horde_Imap_Client_Socket::class);

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
			->willReturn('pam@stardew-bus-service.com');
		$this->calendarManager->expects(self::once())
			->method('handleIMipRequest')
			->willThrowException(new \Exception('Calendar error'));
		$this->logger->expects(self::once())
			->method('error');
		$this->messageMapper->expects(self::once())
			->method('updateImipData')
			->with(self::callback(function (Message $msg) {
				return $msg->isImipProcessed() === true && $msg->isImipError() === true;
			}));

		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($imapClient);

		$rawMessage = 'Raw message content';
		$this->imapMessageMapper->expects(self::once())
			->method('getFullText')
			->with($imapClient, 'INBOX', 1, 'vincent', false)
			->willReturn($rawMessage);

		$imapClient->expects(self::once())
			->method('logout');

		// Mock the attachment service
		$attachment = $this->createMock(\OCA\Mail\Db\LocalAttachment::class);
		$this->attachmentService->expects(self::once())
			->method('addFileFromString')
			->with(
				'vincent',
				'original-message.eml',
				'message/rfc822',
				$rawMessage
			)
			->willReturn($attachment);

		// Mock the mail transmission
		$this->mailTransmission->expects(self::once())
			->method('sendMessage')
			->with(
				$account,
				self::callback(function (LocalMessage $localMessage) use ($account) {
					return $localMessage->getType() === LocalMessage::TYPE_OUTGOING
						&& $localMessage->getAccountId() === $account->getId()
						&& $localMessage->getSubject() === '[ERROR] Calendar invitation processing failed: No Subject'
						&& $localMessage->getHtml() === false
						&& count($localMessage->getRecipients()) === 1
						&& $localMessage->getRecipients()[0]->getEmail() === $account->getEmail();
				})
			);

		$this->service->process();
	}
}
