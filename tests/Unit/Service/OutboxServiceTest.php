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
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMailboxMessage;
use OCA\Mail\Db\LocalMailboxMessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class OutboxServiceTest extends TestCase {


	/** @var IMailTransmission|\PHPUnit\Framework\MockObject\MockObject */
	private $transmission;

	/** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
	private $logger;

	/** @var LocalMailboxMessageMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $mapper;

	/** @var OutboxService */
	private $outboxService;

	/** @var string */
	private $userId;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mapper = $this->createMock(LocalMailboxMessageMapper::class);
		$this->outboxService = new OutboxService(
			$this->transmission,
			$this->logger,
			$this->mapper
		);
		$this->userId = 'linus';
		$this->time = $this->createMock(ITimeFactory::class);
	}

	public function testGetMessages(): void {
		$this->mapper->expects($this->once())
			->method('getAllForUser')
			->with($this->userId)
			->willReturn([
				[
					'id' => 1,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Test',
					'body' => 'Test',
					'html' => false,
					'mdn' => false,
					'reply_to_message_id' => null
				],
				[
					'id' => 2,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Second Test',
					'body' => 'Second Test',
					'html' => true,
					'mdn' => false,
					'reply_to_message_id' => null
				]
			]);

		$this->outboxService->getMessages($this->userId);
	}

	public function testGetMessagesNoneFound(): void {
		$this->mapper->expects($this->once())
			->method('getAllForUser')
			->with($this->userId)
			->willThrowException(new Exception());

		$this->expectException(ServiceException::class);
		$this->outboxService->getMessages($this->userId);
	}

	public function testGetMessage(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('557765fgfdgsd@startdewvalley.com');

		$this->mapper->expects($this->once())
			->method('find')
			->with(1)
			->willReturn($message);

		$this->outboxService->getMessage(1);
	}

	public function testNoMessage(): void {
		$this->mapper->expects($this->once())
			->method('find')
			->with(1)
			->willThrowException(new DoesNotExistException('Could not fetch any messages'));

		$this->expectException(ServiceException::class);
		$this->outboxService->getMessage(1);
	}

	public function testDeleteMessage(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('sdasas3354654565@startdewvalley.com');

		$this->mapper->expects($this->once())
			->method('deleteWithRelated')
			->with($message, $this->userId);

		$this->outboxService->deleteMessage($message, $this->userId);
	}

	public function testDeleteMessageWithException(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('sda4sfdfsdff4fsefd@startdewvalley.com');

		$this->mapper->expects($this->once())
			->method('deleteWithRelated')
			->with($message, $this->userId)
			->willThrowException(new Exception());

		$this->expectException(ServiceException::class);
		$this->outboxService->deleteMessage($message, $this->userId);
	}

	public function testSaveMessage(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('7832646sdghasssgd@startdewvalley.com');
		$recipients = [
			[
				'label' => 'Lewis',
				'email' => 'tent-living@startdewvalley.com'
			]
		];
		$attachmentIds = [
			1, 2, 3
		];

		$this->mapper->expects($this->once())
			->method('saveWithRelatedData')
			->with($message, $recipients, $attachmentIds);

		$this->outboxService->saveMessage($message, $recipients, $attachmentIds);
	}

	public function testSaveMessageNoAttachments(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('3489732647832646sdghasgd@startdewvalley.com');
		$recipients = [
			[
				'label' => 'Pam',
				'email' => 'BuyMeAnAle@startdewvalley.com'
			]
		];

		$this->mapper->expects($this->once())
			->method('saveWithRelatedData')
			->with($message, $recipients);

		$this->outboxService->saveMessage($message, $recipients);
	}

	public function testSaveMessageError(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];

		$this->mapper->expects($this->once())
			->method('saveWithRelatedData')
			->with($message, $recipients)
			->willThrowException(new Exception());

		$this->expectException(ServiceException::class);
		$this->outboxService->saveMessage($message, $recipients);
	}

	public function testSendMessage(): void {
		$message = new LocalMailboxMessage();
		$message->setId(1);
		$recipients = [
			[
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		$attachments = [
			[
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => $this->time->getTime()
			]
		];
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $this->userId
		]);

		$this->mapper->expects($this->once())
			->method('getRelatedData')
			->with($message->getId(), $account->getUserId())
			->willReturn(['recipients' => $recipients, 'attachments' => $attachments]);
		$this->transmission->expects($this->once())
			->method('sendLocalMessage')
			->with($account, $message, $recipients, $attachments);
		$this->mapper->expects($this->once())
			->method('deleteWithRelated')
			->with($message, $this->userId);

		$this->outboxService->sendMessage($message, $account);
	}
}
