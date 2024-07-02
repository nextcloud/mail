<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\Send;

use ChristophWurst\Nextcloud\Testing\TestCase;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\Chain;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use OCA\Mail\Send\SentMailboxHandler;
use OCA\Mail\Service\Attachment\AttachmentService;
use PHPUnit\Framework\MockObject\MockObject;

class ChainTest extends TestCase {
	private Chain $chain;
	private SentMailboxHandler|MockObject $sentMailboxHandler;
	private MockObject|AntiAbuseHandler $antiAbuseHandler;
	private SendHandler|MockObject $sendHandler;
	private MockObject|CopySentMessageHandler $copySentMessageHandler;
	private MockObject|FlagRepliedMessageHandler $flagRepliedMessageHandler;
	private MockObject|MessageMapper $messageMapper;
	private AttachmentService|MockObject $attachmentService;
	private MockObject|LocalMessageMapper $localMessageMapper;

	protected function setUp(): void {
		$this->sentMailboxHandler = $this->createMock(SentMailboxHandler::class);
		$this->antiAbuseHandler = $this->createMock(AntiAbuseHandler::class);
		$this->sendHandler = $this->createMock(SendHandler::class);
		$this->copySentMessageHandler = $this->createMock(CopySentMessageHandler::class);
		$this->flagRepliedMessageHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->localMessageMapper = $this->createMock(LocalMessageMapper::class);
		$this->chain = new Chain($this->sentMailboxHandler,
			$this->antiAbuseHandler,
			$this->sendHandler,
			$this->copySentMessageHandler,
			$this->flagRepliedMessageHandler,
			$this->attachmentService,
			$this->localMessageMapper,
		);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$expected = new LocalMessage();
		$expected->setStatus(LocalMessage::STATUS_PROCESSED);
		$expected->setId(100);

		$this->sentMailboxHandler->expects(self::once())
			->method('setNext');
		$this->sentMailboxHandler->expects(self::once())
			->method('process')
			->with($account, $localMessage)
			->willReturn($expected);
		$this->attachmentService->expects(self::once())
			->method('deleteLocalMessageAttachments')
			->with($account->getUserId(), $expected->getId());
		$this->localMessageMapper->expects(self::once())
			->method('deleteWithRecipients')
			->with($expected);
		$this->localMessageMapper->expects(self::never())
			->method('update');

		$this->chain->process($account, $localMessage);
	}

	public function testProcessNotProcessed() {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$expected = new LocalMessage();
		$expected->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$expected->setId(100);

		$this->sentMailboxHandler->expects(self::once())
			->method('setNext');
		$this->sentMailboxHandler->expects(self::once())
			->method('process')
			->with($account, $localMessage)
			->willReturn($expected);
		$this->attachmentService->expects(self::never())
			->method('deleteLocalMessageAttachments');
		$this->localMessageMapper->expects(self::never())
			->method('deleteWithRecipients');
		$this->localMessageMapper->expects(self::once())
			->method('update')
			->with($expected)
			->willReturn($expected);

		$this->chain->process($account, $localMessage);
	}
}
