<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Service\TransmissionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\SimpleFS\InMemoryFile;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class TransmissionServiceTest extends TestCase {

	private GroupsIntegration|MockObject $groupsIntegration;
	private AttachmentService|MockObject $attachmentService;
	private LoggerInterface|MockObject $logger;
	private SmimeService|MockObject $smimeService;
	private MockObject|TransmissionService $transmissionService;

	protected function setUp(): void {
		parent::setUp();

		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->smimeService = $this->createMock(SmimeService::class);
		$this->groupsIntegration = $this->createMock(GroupsIntegration::class);
		$this->transmissionService = new TransmissionService(
			$this->groupsIntegration,
			$this->attachmentService,
			$this->logger,
			$this->smimeService,
		);
	}

	public function testGetAddressList() {
		$expected = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$recipient = new Recipient();
		$recipient->setLabel('Bob');
		$recipient->setEmail('bob@test.com');
		$recipient->setType(Recipient::TYPE_TO);
		$localMessage = new LocalMessage();
		$localMessage->setRecipients([$recipient]);

		$this->groupsIntegration->expects(self::once())
			->method('expand')
			->willReturn([$recipient]);

		$actual = $this->transmissionService->getAddressList($localMessage, Recipient::TYPE_TO);
		$this->assertEquals($expected, $actual);
	}

	public function testGetAttachments() {
		$id = 1;
		$expected = [[
			'type' => 'local',
			'id' => $id
		]];
		$attachment = new LocalAttachment();
		$attachment->setId($id);
		$localMessage = new LocalMessage();
		$localMessage->setAttachments([$attachment]);

		$actual = $this->transmissionService->getAttachments($localMessage);
		$this->assertEquals($expected, $actual);
	}

	public function testHandleAttachment(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('test.txt');
		$attachment->setMimeType('text/plain');

		$file = new InMemoryFile(
			'test.txt',
			"Hello, I'm a test file."
		);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);
		$this->logger->expects(self::never())
			->method('warning');

		$part = $this->transmissionService->handleAttachment($account, ['id' => 1, 'type' => 'local']);

		$this->assertEquals('test.txt', $part->getContentTypeParameter('name'));
	}

	public function testHandleAttachmentNoId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->logger->expects(self::once())
			->method('warning');

		$this->transmissionService->handleAttachment($account, ['type' => 'local']);
	}

	public function testHandleAttachmentNotFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willThrowException(new AttachmentNotFoundException());
		$this->logger->expects(self::once())
			->method('warning');

		$this->transmissionService->handleAttachment($account, ['id' => 1, 'type' => 'local']);
	}

	public function testGetSignMimePart() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeSign(true);
		$localMessage->setSmimeCertificateId(1);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willReturn($smimeCertificate);
		$this->smimeService->expects(self::once())
			->method('signMimePart');

		$this->transmissionService->getSignMimePart($localMessage, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_RAW, $localMessage->getStatus());
	}

	public function testGetSignMimePartNoCertId() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeSign(true);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->smimeService->expects(self::never())
			->method('findCertificate');
		$this->smimeService->expects(self::never())
			->method('signMimePart');

		$this->expectException(ServiceException::class);
		$this->transmissionService->getSignMimePart($localMessage, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_NO_CERT_ID, $localMessage->getStatus());
	}

	public function testGetSignMimePartNoCertFound() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeSign(true);
		$localMessage->setSmimeCertificateId(1);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willThrowException(new DoesNotExistException(''));
		$this->smimeService->expects(self::never())
			->method('signMimePart');

		$this->expectException(ServiceException::class);
		$this->transmissionService->getSignMimePart($localMessage, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_CERT, $localMessage->getStatus());
	}

	public function testGetSignMimePartFailedSigning() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeSign(true);
		$localMessage->setSmimeCertificateId(1);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willReturn($smimeCertificate);
		$this->smimeService->expects(self::once())
			->method('signMimePart')
			->willThrowException(new SmimeSignException());

		$this->expectException(ServiceException::class);
		$this->transmissionService->getSignMimePart($localMessage, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_FAIL, $localMessage->getStatus());
	}

	public function testGetEncryptMimePart() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeEncrypt(true);
		$localMessage->setSmimeCertificateId(1);
		$to = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$cc = new AddressList([]);
		$bcc = new AddressList([]);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificatesByAddressList')
			->willReturn([$smimeCertificate]);
		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willReturn($smimeCertificate);
		$this->smimeService->expects(self::once())
			->method('encryptMimePart');

		$this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_RAW, $localMessage->getStatus());
	}

	public function testGetEncryptMimePartNoCertId() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeEncrypt(true);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$to = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$cc = new AddressList([]);
		$bcc = new AddressList([]);

		$this->expectException(ServiceException::class);
		$this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYPT_NO_CERT_ID, $localMessage->getStatus());
	}

	public function testGetEncryptMimePartNoAddressCerts() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeEncrypt(true);
		$localMessage->setSmimeCertificateId(1);
		$to = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$cc = new AddressList([]);
		$bcc = new AddressList([]);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificatesByAddressList')
			->willThrowException(new ServiceException());
		$this->smimeService->expects(self::never())
			->method('findCertificate');
		$this->smimeService->expects(self::never())
			->method('encryptMimePart');

		$this->expectException(ServiceException::class);
		$this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYT_FAIL, $localMessage->getStatus());
	}

	public function testGetEncryptMimePartNoCert() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeEncrypt(true);
		$localMessage->setSmimeCertificateId(1);
		$to = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$cc = new AddressList([]);
		$bcc = new AddressList([]);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificatesByAddressList')
			->willReturn([$smimeCertificate]);
		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willThrowException(new DoesNotExistException(''));
		$this->smimeService->expects(self::never())
			->method('encryptMimePart');

		$this->expectException(ServiceException::class);
		$this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYPT_CERT, $localMessage->getStatus());
	}

	public function testGetEncryptMimePartEncryptFail() {
		$send = new \Horde_Mime_Part();
		$send->setContents('Test');
		$localMessage = new LocalMessage();
		$localMessage->setSmimeEncrypt(true);
		$localMessage->setSmimeCertificateId(1);
		$to = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$cc = new AddressList([]);
		$bcc = new AddressList([]);
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$smimeCertificate = new SmimeCertificate();
		$smimeCertificate->setCertificate('123');

		$this->smimeService->expects(self::once())
			->method('findCertificatesByAddressList')
			->willReturn([$smimeCertificate]);
		$this->smimeService->expects(self::once())
			->method('findCertificate')
			->willReturn($smimeCertificate);
		$this->smimeService->expects(self::once())
			->method('encryptMimePart')
			->willThrowException(new ServiceException());

		$this->expectException(ServiceException::class);
		$this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $send);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYT_FAIL, $localMessage->getStatus());
	}

	public function testHandleAttachmentKeepAdditionalContentTypeParameters(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('event.ics');
		$attachment->setMimeType('text/calendar; method=REQUEST');

		$file = new InMemoryFile(
			'event.ics',
			"BEGIN:VCALENDAR\nEND:VCALENDAR"
		);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$part = $this->transmissionService->handleAttachment($account, ['id' => 1, 'type' => 'local']);

		$this->assertEquals('event.ics', $part->getContentTypeParameter('name'));
		$this->assertEquals('REQUEST', $part->getContentTypeParameter('method'));
	}
}
