<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\IMAP\ImapTransmissionConnector;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\MimeMessage;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Service\TransmissionService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\SimpleFS\InMemoryFile;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class ImapTransmissionConnectorTest extends TestCase {

	private ProtocolFactory|MockObject $protocolFactory;
	private TransmissionService|MockObject $transmissionService;
	private AliasesService|MockObject $aliasesService;
	private SmtpClientFactory|MockObject $smtpClientFactory;
	private MimeMessage|MockObject $mimeMessage;
	private MessageMapper|MockObject $messageMapper;
	private MailboxMapper|MockObject $mailboxMapper;
	private PerformanceLogger|MockObject $performanceLogger;
	private SmimeService|MockObject $smimeService;
	private AttachmentService|MockObject $attachmentService;
	private LoggerInterface|MockObject $logger;
	private ImapTransmissionConnector $connector;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->transmissionService = $this->createMock(TransmissionService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->mimeMessage = $this->createMock(MimeMessage::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->smimeService = $this->createMock(SmimeService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->connector = new ImapTransmissionConnector(
			$this->protocolFactory,
			$this->transmissionService,
			$this->aliasesService,
			$this->smtpClientFactory,
			$this->mimeMessage,
			$this->messageMapper,
			$this->mailboxMapper,
			$this->performanceLogger,
			$this->smimeService,
			$this->attachmentService,
			$this->logger,
		);
	}

	/**
	 * @return mixed
	 */
	private function callPrivate(string $method, array $args) {
		$reflection = new ReflectionMethod(ImapTransmissionConnector::class, $method);
		$reflection->setAccessible(true);
		return $reflection->invoke($this->connector, ...$args);
	}

	public function testBuildAttachmentMimePart(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('test.txt');
		$attachment->setMimeType('text/plain');
		$attachment->setDisposition(LocalAttachment::DISPOSITION_ATTACHMENT);

		$file = new InMemoryFile(
			'test.txt',
			"Hello, I'm a test file."
		);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);
		$this->logger->expects(self::never())
			->method('warning');

		$part = $this->callPrivate('buildAttachmentMimePart', [$account, ['id' => 1, 'type' => 'local']]);

		$this->assertEquals('test.txt', $part->getContentTypeParameter('name'));
	}

	public function testBuildAttachmentMimePartInlineWithContentId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('logo.png');
		$attachment->setMimeType('image/png');
		$attachment->setDisposition(LocalAttachment::DISPOSITION_INLINE);
		$attachment->setContentId('img001@example.com');

		$file = new InMemoryFile('logo.png', 'fake png content');

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$part = $this->callPrivate('buildAttachmentMimePart', [$account, ['id' => 1, 'type' => 'local']]);

		$this->assertEquals('inline', $part->getDisposition());
		$this->assertEquals('img001@example.com', $part->getContentId());
		$this->assertEquals('logo.png', $part->getContentTypeParameter('name'));
	}

	public function testBuildAttachmentMimePartNoId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->logger->expects(self::once())
			->method('warning');

		$this->callPrivate('buildAttachmentMimePart', [$account, ['type' => 'local']]);
	}

	public function testBuildAttachmentMimePartNotFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willThrowException(new AttachmentNotFoundException());
		$this->logger->expects(self::once())
			->method('warning');

		$this->callPrivate('buildAttachmentMimePart', [$account, ['id' => 1, 'type' => 'local']]);
	}

	public function testBuildAttachmentMimePartKeepAdditionalContentTypeParameters(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$attachment = new LocalAttachment();
		$attachment->setFileName(null);
		$attachment->setMimeType('text/calendar; method=REQUEST; charset="utf-8"; name=event.ics');
		// iMIP attachments must not carry a Content-Disposition header.
		// See https://github.com/nextcloud/mail/issues/10416
		$attachment->setDisposition(LocalAttachment::DISPOSITION_OMIT);
		$file = new InMemoryFile(
			'event.ics',
			"BEGIN:VCALENDAR\nEND:VCALENDAR"
		);
		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$part = $this->callPrivate('buildAttachmentMimePart', [$account, ['id' => 1, 'type' => 'local']]);

		$this->assertEquals('text/calendar', $part->getType());
		$this->assertEquals('REQUEST', $part->getContentTypeParameter('method'));
		$this->assertEquals('utf-8', $part->getContentTypeParameter('charset'));
		$this->assertEquals('event.ics', $part->getContentTypeParameter('name'));
	}

	public function testBuildAttachmentMimePartImipOmitsContentDisposition(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$attachment = new LocalAttachment();
		$attachment->setFileName(null);
		$attachment->setMimeType('text/calendar; method=REQUEST; charset="utf-8"; name=event.ics');
		// iMIP attachments must not carry a Content-Disposition header.
		// See https://github.com/nextcloud/mail/issues/10416
		$attachment->setDisposition(LocalAttachment::DISPOSITION_OMIT);
		$file = new InMemoryFile(
			'event.ics',
			"BEGIN:VCALENDAR\nEND:VCALENDAR"
		);
		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$part = $this->callPrivate('buildAttachmentMimePart', [$account, ['id' => 1, 'type' => 'local']]);

		$this->assertEquals('', $part->getDisposition());
	}

	public function testApplySmimeSignature() {
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

		$this->callPrivate('applySmimeSignature', [$localMessage, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_RAW, $localMessage->getStatus());
	}

	public function testApplySmimeSignatureNoCertId() {
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
		$this->callPrivate('applySmimeSignature', [$localMessage, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_NO_CERT_ID, $localMessage->getStatus());
	}

	public function testApplySmimeSignatureNoCertFound() {
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
		$this->callPrivate('applySmimeSignature', [$localMessage, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_CERT, $localMessage->getStatus());
	}

	public function testApplySmimeSignatureFailedSigning() {
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
		$this->callPrivate('applySmimeSignature', [$localMessage, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_SIGN_FAIL, $localMessage->getStatus());
	}

	public function testApplySmimeEncryption() {
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

		$this->callPrivate('applySmimeEncryption', [$localMessage, $to, $cc, $bcc, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_RAW, $localMessage->getStatus());
	}

	public function testApplySmimeEncryptionNoCertId() {
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
		$this->callPrivate('applySmimeEncryption', [$localMessage, $to, $cc, $bcc, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYPT_NO_CERT_ID, $localMessage->getStatus());
	}

	public function testApplySmimeEncryptionNoAddressCerts() {
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

		$this->smimeService->expects(self::once())
			->method('findCertificatesByAddressList')
			->willThrowException(new ServiceException());
		$this->smimeService->expects(self::never())
			->method('findCertificate');
		$this->smimeService->expects(self::never())
			->method('encryptMimePart');

		$this->expectException(ServiceException::class);
		$this->callPrivate('applySmimeEncryption', [$localMessage, $to, $cc, $bcc, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYT_FAIL, $localMessage->getStatus());
	}

	public function testApplySmimeEncryptionNoCert() {
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
		$this->callPrivate('applySmimeEncryption', [$localMessage, $to, $cc, $bcc, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYPT_CERT, $localMessage->getStatus());
	}

	public function testApplySmimeEncryptionEncryptFail() {
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
		$this->callPrivate('applySmimeEncryption', [$localMessage, $to, $cc, $bcc, $account, $send]);
		$this->assertEquals(LocalMessage::STATUS_SMIME_ENCRYT_FAIL, $localMessage->getStatus());
	}

	public function testSaveMessageIncludesAttachments(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setName('Drafts');

		$localMessage = new LocalMessage();
		$localMessage->setSubject('Hello');
		$localMessage->setBodyPlain('Body');
		$localMessage->setHtml(false);

		$recipient = new Recipient();
		$recipient->setLabel('Bob');
		$recipient->setEmail('bob@test.com');
		$recipient->setType(Recipient::TYPE_TO);

		$this->transmissionService->method('getAddressList')
			->willReturnCallback(function (LocalMessage $message, int $type) use ($recipient) {
				if ($type === Recipient::TYPE_TO) {
					return new AddressList([Address::fromRaw($recipient->getLabel(), $recipient->getEmail())]);
				}
				return new AddressList([]);
			});
		$this->transmissionService->method('getAttachments')
			->willReturn([['type' => 'local', 'id' => 1]]);

		$attachment = new LocalAttachment();
		$attachment->setFileName('test.txt');
		$attachment->setMimeType('text/plain');
		$attachment->setDisposition(LocalAttachment::DISPOSITION_ATTACHMENT);
		$file = new InMemoryFile('test.txt', 'Attachment contents');
		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$this->performanceLogger->method('start')
			->willReturn($this->createMock(PerformanceLoggerTask::class));
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createMock(Horde_Imap_Client_Socket::class));

		$capturedRaw = null;
		$this->messageMapper->expects(self::once())
			->method('save')
			->willReturnCallback(function ($client, $mailboxArg, $raw) use (&$capturedRaw) {
				$capturedRaw = $raw;
				return null;
			});

		$this->connector->saveMessage($account, $mailbox, $localMessage);

		$this->assertNotNull($capturedRaw);
		$this->assertStringContainsString('test.txt', $capturedRaw);
		$this->assertStringContainsString('Attachment contents', $capturedRaw);
	}
}
