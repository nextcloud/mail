<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
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
 *
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\EventDispatcher\EventDispatcher;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class OutboxServiceTest extends TestCase {
	/** @var MailTransmission|MockObject */
	private $transmission;

	/** @var LocalMessageMapper|MockObject */
	private $mapper;

	/** @var OutboxService */
	private $outboxService;

	/** @var string */
	private $userId;

	/** @var ITimeFactory|MockObject */
	private $time;

	/** @var AttachmentService|MockObject */
	private $attachmentService;

	/** @var IMAPClientFactory|MockObject */
	private $clientFactory;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->transmission = $this->createMock(MailTransmission::class);
		$this->mapper = $this->createMock(LocalMessageMapper::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->outboxService = new OutboxService(
			$this->transmission,
			$this->mapper,
			$this->attachmentService,
			$this->createMock(EventDispatcher::class),
			$this->clientFactory,
			$this->mailManager,
			$this->accountService,
			$this->timeFactory,
			$this->logger,
		);
		$this->userId = 'linus';
		$this->time = $this->createMock(ITimeFactory::class);
	}

	public function testGetMessages(): void {
		$this->mapper->expects(self::once())
			->method('getAllForUser')
			->with($this->userId)
			->willReturn([
				[
					'id' => 1,
					'type' => 0,
					'account_id' => 1,
					'alias_id' => 2,
					'send_at' => $this->time->getTime(),
					'subject' => 'Test',
					'body' => 'Test',
					'html' => false,
					'reply_to_id' => null,
					'draft_id' => 99

				],
				[
					'id' => 2,
					'type' => 0,
					'account_id' => 1,
					'alias_id' => 2,
					'send_at' => $this->time->getTime(),
					'subject' => 'Second Test',
					'body' => 'Second Test',
					'html' => true,
					'reply_to_id' => null,
					'draft_id' => null
				]
			]);

		$this->outboxService->getMessages($this->userId);
	}

	public function testGetMessagesNoneFound(): void {
		$this->mapper->expects(self::once())
			->method('getAllForUser')
			->with($this->userId)
			->willThrowException(new Exception());

		$this->expectException(Exception::class);
		$this->outboxService->getMessages($this->userId);
	}

	public function testGetMessage(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');

		$this->mapper->expects(self::once())
			->method('findById')
			->with(1, $this->userId)
			->willReturn($message);

		$this->outboxService->getMessage(1, $this->userId);
	}

	public function testNoMessage(): void {
		$this->mapper->expects(self::once())
			->method('findById')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Could not fetch any messages'));

		$this->expectException(DoesNotExistException::class);
		$this->outboxService->getMessage(1, $this->userId);
	}

	public function testDeleteMessage(): void {
		$message = new LocalMessage();
		$message->setId(10);
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');

		$this->attachmentService->expects(self::once())
			->method('deleteLocalMessageAttachments')
			->with($this->userId, $message->getId());
		$this->mapper->expects(self::once())
			->method('deleteWithRecipients')
			->with($message);

		$this->outboxService->deleteMessage($this->userId, $message);
	}

	public function testSaveMessage(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$to = [
			[
				'label' => 'Lewis',
				'email' => 'tent-living@startdewvalley.com',
				'type' => Recipient::TYPE_TO,
			]
		];
		$cc = [];
		$bcc = [];
		$attachments = [[]];
		$attachmentIds = [1];
		$rTo = Recipient::fromParams([
			'label' => 'Lewis',
			'email' => 'tent-living@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message2 = $message;
		$message2->setId(10);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);

		$this->mapper->expects(self::once())
			->method('saveWithRecipients')
			->with($message, [$rTo], $cc, $bcc)
			->willReturn($message2);
		$this->clientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$this->attachmentService->expects(self::once())
			->method('handleAttachments')
			->with($account, $attachments, $client)
			->willReturn($attachmentIds);
		$this->attachmentService->expects(self::once())
			->method('saveLocalMessageAttachments')
			->with($this->userId, 10, $attachmentIds);

		$this->outboxService->saveMessage($account, $message, $to, $cc, $bcc, $attachments);
	}

	public function testSaveMessageNoAttachments(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$to = [
			[
				'label' => 'Lewis',
				'email' => 'tent-living@startdewvalley.com',
				'type' => Recipient::TYPE_TO,
			]
		];
		$cc = [];
		$bcc = [];
		$attachments = [];
		$rTo = Recipient::fromParams([
			'label' => 'Lewis',
			'email' => 'tent-living@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message2 = $message;
		$message2->setId(10);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);

		$this->mapper->expects(self::once())
			->method('saveWithRecipients')
			->with($message, [$rTo], $cc, $bcc)
			->willReturn($message2);
		$this->clientFactory->expects(self::never())
			->method('getClient');
		$this->attachmentService->expects(self::never())
			->method('handleAttachments');
		$this->attachmentService->expects(self::never())
			->method('saveLocalMessageAttachments');

		$result = $this->outboxService->saveMessage($account, $message, $to, $cc, $bcc, $attachments);
		$this->assertEquals($message2->getId(), $result->getId());
		$this->assertEmpty($result->getAttachments());
	}

	public function testUpdateMessage(): void {
		$message = new LocalMessage();
		$message->setId(10);
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$old = Recipient::fromParams([
			'label' => 'Pam',
			'email' => 'BuyMeAnAle@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message->setRecipients([$old]);
		$to = [
			[
				'label' => 'Linus',
				'email' => 'tent-living@startdewvalley.com',
				'type' => Recipient::TYPE_TO,
			]
		];
		$cc = [];
		$bcc = [];
		$attachments = [['type' => '']];
		$attachmentIds = [3];
		$rTo = Recipient::fromParams([
			'label' => 'Linus',
			'email' => 'tent-living@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message2 = $message;
		$message2->setRecipients([$rTo]);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);

		$this->mapper->expects(self::once())
			->method('updateWithRecipients')
			->with($message, [$rTo], $cc, $bcc)
			->willReturn($message2);
		$this->clientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$this->attachmentService->expects(self::once())
			->method('handleAttachments')
			->with($account, $attachments, $client)
			->willReturn($attachmentIds);
		$this->attachmentService->expects(self::once())
			->method('updateLocalMessageAttachments')
			->with($this->userId, $message2, $attachmentIds);

		$this->outboxService->updateMessage($account, $message, $to, $cc, $bcc, $attachments);
	}

	public function testUpdateMessageNoAttachments(): void {
		$message = new LocalMessage();
		$message->setId(10);
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abcd');
		$old = Recipient::fromParams([
			'label' => 'Pam',
			'email' => 'BuyMeAnAle@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message->setRecipients([$old]);
		$to = [
			[
				'label' => 'Linus',
				'email' => 'tent-living@startdewvalley.com',
				'type' => Recipient::TYPE_TO,
			]
		];
		$cc = [];
		$bcc = [];
		$attachments = [];
		$rTo = Recipient::fromParams([
			'label' => 'Linus',
			'email' => 'tent-living@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$message2 = $message;
		$message2->setRecipients([$rTo]);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);
		$this->mapper->expects(self::once())
			->method('updateWithRecipients')
			->with($message, [$rTo], $cc, $bcc)
			->willReturn($message2);
		$this->attachmentService->expects(self::once())
			->method('updateLocalMessageAttachments')
			->with($this->userId, $message2, $attachments);
		$this->clientFactory->expects(self::never())
			->method('getClient');
		$this->attachmentService->expects(self::never())
			->method('handleAttachments');
		$result = $this->outboxService->updateMessage($account, $message, $to, $cc, $bcc, $attachments);
		$this->assertEmpty($result->getAttachments());
	}

	public function testSaveMessageError(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$to = [
			[
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com',
				'type' => Recipient::TYPE_TO,
			]
		];
		$rTo = Recipient::fromParams([
			'label' => 'Gunther',
			'email' => 'museum@startdewvalley.com',
			'type' => Recipient::TYPE_TO,
		]);
		$account = $this->createMock(Account::class);

		$this->mapper->expects(self::once())
			->method('saveWithRecipients')
			->with($message, [$rTo], [], [])
			->willThrowException(new Exception());
		$this->attachmentService->expects(self::never())
			->method('saveLocalMessageAttachments');
		$this->expectException(Exception::class);

		$this->outboxService->saveMessage($account, $message, $to, [], []);
	}

	public function testSendMessage(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$recipient = new Recipient();
		$recipient->setEmail('museum@startdewvalley.com');
		$recipient->setLabel('Gunther');
		$recipient->setType(Recipient::TYPE_TO);
		$recipients = [$recipient];
		$attachment = new LocalAttachment();
		$attachment->setMimeType('image/png');
		$attachment->setFileName('SlimesInTheMines.png');
		$attachment->setCreatedAt($this->time->getTime());
		$attachments = [$attachment];
		$message->setRecipients($recipients);
		$message->setAttachments($attachments);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);

		$this->transmission->expects(self::once())
			->method('sendLocalMessage')
			->with($account, $message);
		$this->attachmentService->expects(self::once())
			->method('deleteLocalMessageAttachments')
			->with($account->getUserId(), $message->getId());
		$this->mapper->expects(self::once())
			->method('deleteWithRecipients')
			->with($message);

		$this->outboxService->sendMessage($message, $account);
	}

	public function testSendMessageTransmissionError(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$recipient = new Recipient();
		$recipient->setEmail('museum@startdewvalley.com');
		$recipient->setLabel('Gunther');
		$recipient->setType(Recipient::TYPE_TO);
		$recipients = [$recipient];
		$attachment = new LocalAttachment();
		$attachment->setMimeType('image/png');
		$attachment->setFileName('SlimesInTheMines.png');
		$attachment->setCreatedAt($this->time->getTime());
		$attachments = [$attachment];
		$message->setRecipients($recipients);
		$message->setAttachments($attachments);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);

		$this->transmission->expects(self::once())
			->method('sendLocalMessage')
			->with($account, $message)
			->willThrowException(new ClientException());
		$this->attachmentService->expects(self::never())
			->method('deleteLocalMessageAttachments');
		$this->mapper->expects(self::never())
			->method('deleteWithRecipients');

		$this->expectException(ClientException::class);
		$this->outboxService->sendMessage($message, $account);
	}
}
