<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message as DbMessage;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AntiSpamServiceTest extends TestCase {
	private AntiSpamService $service;
	private IConfig|MockObject $config;
	private DbMessageMapper|MockObject $dbMessageMapper;
	private IMAPClientFactory|MockObject $imapClientFactory;
	private SmtpClientFactory|MockObject $smtpClientFactory;
	private MockObject|ImapMessageMapper $imapMessageMapper;
	private LoggerInterface|MockObject $logger;
	private MockObject|IMailTransmission $transmission;
	private MailManager|MockObject $mailManager;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->dbMessageMapper = $this->createMock(DbMessageMapper::class);
		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->imapMessageMapper = $this->createMock(ImapMessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->service = new AntiSpamService(
			$this->config,
			$this->dbMessageMapper,
			$this->mailManager,
			$this->imapClientFactory,
			$this->smtpClientFactory,
			$this->imapMessageMapper,
			$this->logger,
		);
	}

	public function testSendReportEmailNoEmailFound(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('');
		$this->dbMessageMapper->expects(self::never())
			->method('getIdForUid');

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailNoMessageFound(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$this->dbMessageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(null);
		$this->expectException(ServiceException::class);

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailTransmissionError(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'Learn as Junk',
			'Learn as Junk',
			'test@test.com',
			null,
			null,
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->dbMessageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);

		$this->expectException(ServiceException::class);
		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmail(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(10);
		$mailAccount->setName('Test');
		$mailAccount->setEmail('test@test.com');
		$mailAccount->setUserId('test');
		$account = new Account($mailAccount);
		$dbMessage = new DbMessage();
		$dbMessage->setMailboxId(55);
		$dbMessage->setUid(123);
		$dbMessage->setSubject('Spam Spam and Eggs and Spam');
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $account,
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'Learn as Junk',
			'Learn as Junk',
			'test@test.com',
			null,
			null,
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->dbMessageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with('test', 123)
			->willReturn($dbMessage);
		$this->mailManager->expects(self::exactly(2))
			->method('getMailbox')
			->willReturn($mailbox);
		$this->imapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturn($client);
		$client->expects(self::exactly(2))
			->method('logout');
		$this->imapMessageMapper->expects(self::once())
			->method('getFullText')
			->with($client, $mailbox->getName(), $dbMessage->getUid(), 'test')
			->willReturn('Test');
		$this->smtpClientFactory->expects(self::once())
			->method('create')
			->with($account)
			->willReturn($this->createMock(\Horde_Mail_Transport::class));
		$this->imapMessageMapper->expects(self::once())
			->method('save');
		$this->logger->expects(self::never())
			->method(self::anything());

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailNoSentCopy(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(10);
		$mailAccount->setName('Test');
		$mailAccount->setEmail('test@test.com');
		$mailAccount->setUserId('test');
		$account = new Account($mailAccount);
		$dbMessage = new DbMessage();
		$dbMessage->setMailboxId(55);
		$dbMessage->setUid(123);
		$dbMessage->setSubject('Spam Spam and Eggs and Spam');
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $account,
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'Learn as Not Junk',
			'Learn as Not Junk',
			'test@test.com',
			null,
			null,
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->dbMessageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with('test', 123)
			->willReturn($dbMessage);
		$this->mailManager->expects(self::exactly(2))
			->method('getMailbox')
			->willReturn($mailbox);
		$this->imapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturn($client);
		$client->expects(self::exactly(2))
			->method('logout');
		$this->imapMessageMapper->expects(self::once())
			->method('getFullText')
			->with($client, $mailbox->getName(), $dbMessage->getUid(), 'test')
			->willReturn('Test');
		$this->smtpClientFactory->expects(self::once())
			->method('create')
			->with($account)
			->willReturn($this->createMock(\Horde_Mail_Transport::class));
		$this->imapMessageMapper->expects(self::once())
			->method('save')
			->willThrowException(new \Horde_Imap_Client_Exception());
		$this->logger->expects(self::once())
			->method('error');

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}
}
