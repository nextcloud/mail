<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Provider\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Provider\Command\MessageSend;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\Attachment;
use OCP\Mail\Provider\Exception\SendException;
use OCP\Mail\Provider\Message;
use PHPUnit\Framework\MockObject\MockObject;

class MessageSendTest extends TestCase {

	private IConfig&MockObject $config;
	private ITimeFactory&MockObject $time;
	private AccountService&MockObject $accountService;
	private OutboxService&MockObject $outboxService;
	private AttachmentService&MockObject $attachmentService;
	private MessageSend $commandSend;
	private Message $mailMessage;
	private Attachment $mailAttachment;
	private Account $localAccount;
	private array $localMessageData;
	private array $localAttachmentData;

	protected function setUp(): void {
		parent::setUp();
		// construct mock constructor parameters
		$this->time = $this->createMock(ITimeFactory::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		// construct test object
		$this->commandSend = new MessageSend($this->time, $this->accountService, $this->outboxService, $this->attachmentService);
		// construct mail provider attachment
		$this->mailAttachment = new Attachment(
			'This is the contents of our plan',
			'plan.txt',
			'text/plain'
		);
		// construct mail provider message
		$this->mailMessage = new Message();
		$this->mailMessage->setFrom(new Address('user1@testing.com', 'User One'));
		$this->mailMessage->setTo(new Address('user2@testing.com', 'User Two'));
		$this->mailMessage->setSubject('World domination');
		$this->mailMessage->setBodyPlain('I have the most brilliant plan. Let me tell you all about it. What we do is, we');
		// construct mail app account object
		$this->localAccount = new Account(new MailAccount([
			'accountId' => 100,
			'accountName' => 'User One',
			'emailAddress' => 'user1@testing.com',
			'imapHost' => '',
			'imapPort' => '',
			'imapSslMode' => false,
			'imapUser' => '',
			'smtpHost' => '',
			'smtpPort' => '',
			'smtpSslMode' => false,
			'smtpUser' => '',
		]));
		// construct mail app message object
		$this->localMessageData = [
			'type' => 0,
			'accountId' => 100,
			'subject' => 'World domination',
			'bodyPlain' => 'I have the most brilliant plan. Let me tell you all about it. What we do is, we',
			'html' => false
		];
		// construct mail app attachment object
		$this->localAttachmentData = [
			'id' => null,
			'userId' => null,
			'fileName' => 'event.ics',
			'mimeType' => 'text/plain',
			'createdAt' => null,
			'localMessageId' => null
		];
	}

	public function testPerformWithAttachment(): void {
		// define time factory return
		$this->time->method('getTime')->willReturn(1719792000);
		// define account service returns
		$this->accountService->method('find')->will(
			$this->returnValueMap([
				['user1', 100, $this->localAccount]
			])
		);
		// construct mail app attachment
		$localAttachmentReturned = $this->localAttachmentData;
		$localAttachmentReturned['id'] = 1;
		$localAttachmentReturned['userId'] = 'user1';
		$localAttachmentReturned = LocalAttachment::fromParams($localAttachmentReturned);
		// define attachment service returns
		$this->attachmentService->expects($this->once())->method('addFileFromString')
			->with(
				'user1',
				$this->mailAttachment->getName(),
				$this->mailAttachment->getType(),
				$this->mailAttachment->getContents()
			)->willReturn($localAttachmentReturned);
		// construct mail app message objects
		$localMessageFresh = $this->localMessageData;
		$localMessageFresh['sendAt'] = $this->time->getTime($localMessageFresh);
		$localMessageFresh = LocalMessage::fromParams($localMessageFresh);
		$localMessageReturned = $this->localMessageData;
		$localMessageReturned['id'] = 1;
		$localMessageReturned['recipients'] = [['email' => 'use2@testing.com', 'label' => 'User Two']];
		$localMessageReturned['sendAt'] = $this->time->getTime();
		$localMessageReturned = LocalMessage::fromParams($localMessageReturned);
		// define attachment service returns
		$this->outboxService->expects($this->once())->method('saveMessage')
			->with(
				$this->localAccount,
				$localMessageFresh,
				[['email' => 'user2@testing.com', 'label' => 'User Two']],
				[],
				[],
				[$localAttachmentReturned->jsonSerialize()]
			)->willReturn($localMessageReturned);
		$this->outboxService->expects($this->once())->method('sendMessage')
			->with(
				$localMessageReturned,
				$this->localAccount,
			)->willReturn($localMessageReturned);
		// construct mail provider message with attachment
		$mailMessage = $this->mailMessage;
		$mailMessage->setAttachments($this->mailAttachment);
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithOutAttachment(): void {
		// define time factory return
		$this->time->method('getTime')->willReturn(1719792000);
		// define account service returns
		$this->accountService->method('find')->will(
			$this->returnValueMap([
				['user1', 100, $this->localAccount]
			])
		);
		// construct mail app message objects
		$localMessageFresh = $this->localMessageData;
		$localMessageFresh['sendAt'] = $this->time->getTime($localMessageFresh);
		$localMessageFresh = LocalMessage::fromParams($localMessageFresh);
		$localMessageReturned = $this->localMessageData;
		$localMessageReturned['id'] = 1;
		$localMessageReturned['recipients'] = [['email' => 'use2@testing.com', 'label' => 'User Two']];
		$localMessageReturned['sendAt'] = $this->time->getTime();
		$localMessageReturned = LocalMessage::fromParams($localMessageReturned);
		// define attachment service returns
		$this->outboxService->expects($this->once())->method('saveMessage')
			->with(
				$this->localAccount,
				$localMessageFresh,
				[['email' => 'user2@testing.com', 'label' => 'User Two']],
				[],
				[],
				[]
			)->willReturn($localMessageReturned);
		$this->outboxService->expects($this->once())->method('sendMessage')
			->with(
				$localMessageReturned,
				$this->localAccount,
			)->willReturn($localMessageReturned);
		// construct mail provider message
		$mailMessage = $this->mailMessage;
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithInvalidAttachment(): void {
		// construct mail provider message with attachment
		$mailMessage = $this->mailMessage;
		$mailMessage->setAttachments(new Attachment('This is the contents of our plan', 'plan.txt', ''));
		// define exception condition
		$this->expectException(SendException::class);
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithInvalidTo(): void {
		// construct mail provider message
		$mailMessage = $this->mailMessage;
		$mailMessage->setTo(new Address('', 'User Two'));
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Invalid Message Parameter: All TO, CC and BCC addresses MUST contain at least an email address');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithInvalidCc(): void {
		// construct mail provider message
		$mailMessage = $this->mailMessage;
		$mailMessage->setCc(new Address('', 'User Three'));
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Invalid Message Parameter: All TO, CC and BCC addresses MUST contain at least an email address');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithInvalidBcc(): void {
		// construct mail provider message
		$mailMessage = $this->mailMessage;
		$mailMessage->setBcc(new Address('', 'User Three'));
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Invalid Message Parameter: All TO, CC and BCC addresses MUST contain at least an email address');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithAccountServiceFailure(): void {
		// define time factory return
		$this->time->method('getTime')->willReturn(1719792000);
		// define account service returns
		$this->accountService->method('find')->will(
			$this->throwException(new ClientException('Something went wrong'))
		);

		// construct mail provider message
		$mailMessage = $this->mailMessage;
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Error: occurred while retrieving mail account details');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithAttachmentServiceFailure(): void {
		// define time factory return
		$this->time->method('getTime')->willReturn(1719792000);
		// define account service returns
		$this->accountService->method('find')->will(
			$this->returnValueMap([
				['user1', 100, $this->localAccount]
			])
		);
		// define attachment service returns
		$this->attachmentService->expects($this->once())->method('addFileFromString')
			->with(
				'user1',
				$this->mailAttachment->getName(),
				$this->mailAttachment->getType(),
				$this->mailAttachment->getContents()
			)->will(
				$this->throwException(new UploadException('Something went wrong'))
			);
		// construct mail provider message with attachment
		$mailMessage = $this->mailMessage;
		$mailMessage->setAttachments($this->mailAttachment);
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Error: occurred while saving mail message attachment');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}

	public function testPerformWithOutboxServiceSendFailure(): void {
		// define time factory return
		$this->time->method('getTime')->willReturn(1719792000);
		// define account service returns
		$this->accountService->method('find')->will(
			$this->returnValueMap([
				['user1', 100, $this->localAccount]
			])
		);
		// construct mail app message objects
		$localMessageFresh = $this->localMessageData;
		$localMessageFresh['sendAt'] = $this->time->getTime($localMessageFresh);
		$localMessageFresh = LocalMessage::fromParams($localMessageFresh);
		$localMessageReturned = $this->localMessageData;
		$localMessageReturned['id'] = 1;
		$localMessageReturned['recipients'] = [['email' => 'use2@testing.com', 'label' => 'User Two']];
		$localMessageReturned['sendAt'] = $this->time->getTime();
		$localMessageReturned = LocalMessage::fromParams($localMessageReturned);
		// define outbox service returns
		$this->outboxService->expects($this->once())->method('saveMessage')
			->with(
				$this->localAccount,
				$localMessageFresh,
				[['email' => 'user2@testing.com', 'label' => 'User Two']],
				[],
				[],
				[]
			)->willReturn($localMessageReturned);
		$this->outboxService->expects($this->once())->method('sendMessage')
			->with(
				$localMessageReturned,
				$this->localAccount,
			)->will(
				$this->throwException(new \Exception('Something went wrong'))
			);
		// construct mail provider message
		$mailMessage = $this->mailMessage;
		// define exception condition
		$this->expectException(SendException::class);
		$this->expectExceptionMessage('Error: occurred while sending mail message');
		// test send message
		$this->commandSend->perform('user1', '100', $mailMessage);
	}
}
