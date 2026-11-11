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
use OCA\Mail\Exception\SendInProgressException;
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\Chain;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\ICacheFactory;
use OCP\IMemcache;
use PHPUnit\Framework\MockObject\MockObject;

class ChainTest extends TestCase {
	private Chain $chain;
	private MockObject|AntiAbuseHandler $antiAbuseHandler;
	private SendHandler|MockObject $sendHandler;
	private MockObject|FlagRepliedMessageHandler $flagRepliedMessageHandler;
	private AttachmentService|MockObject $attachmentService;
	private MockObject|LocalMessageMapper $localMessageMapper;
	private MockObject|IMemcache $sendLocks;

	protected function setUp(): void {
		$this->antiAbuseHandler = $this->createMock(AntiAbuseHandler::class);
		$this->sendHandler = $this->createMock(SendHandler::class);
		$this->flagRepliedMessageHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->localMessageMapper = $this->createMock(LocalMessageMapper::class);
		$this->sendLocks = $this->createMock(IMemcache::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')
			->willReturn($this->sendLocks);
		$this->chain = new Chain(
			$this->antiAbuseHandler,
			$this->sendHandler,
			$this->flagRepliedMessageHandler,
			$this->attachmentService,
			$this->localMessageMapper,
			$cacheFactory,
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

		$this->sendLocks->method('add')
			->willReturn(true);
		$this->antiAbuseHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->sendHandler);
		$this->sendHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->flagRepliedMessageHandler);
		$this->antiAbuseHandler->expects(self::once())
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

	public function testProcessNotProcessed(): void {
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

		$this->sendLocks->method('add')
			->willReturn(true);
		$this->antiAbuseHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->sendHandler);
		$this->sendHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->flagRepliedMessageHandler);
		$this->antiAbuseHandler->expects(self::once())
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

	public function testProcessReleasesLock(): void {
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

		$this->sendLocks->expects(self::once())
			->method('add')
			->with('100', 1, Chain::SEND_LOCK_TTL)
			->willReturn(true);
		$this->sendLocks->expects(self::once())
			->method('remove')
			->with('100');
		$this->antiAbuseHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->sendHandler);
		$this->sendHandler->expects(self::once())
			->method('setNext')
			->willReturn($this->flagRepliedMessageHandler);
		$this->antiAbuseHandler->expects(self::once())
			->method('process')
			->with($account, $localMessage)
			->willReturn($expected);

		$this->chain->process($account, $localMessage);
	}

	public function testProcessAlreadyBeingSent(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->sendLocks->expects(self::once())
			->method('add')
			->with('100', 1, Chain::SEND_LOCK_TTL)
			->willReturn(false);
		$this->sendLocks->expects(self::never())
			->method('remove');
		$this->antiAbuseHandler->expects(self::never())
			->method('process');
		$this->localMessageMapper->expects(self::never())
			->method('deleteWithRecipients');
		$this->localMessageMapper->expects(self::never())
			->method('update');
		$this->expectException(SendInProgressException::class);

		$this->chain->process($account, $localMessage);
	}

	public function testIsSending(): void {
		$localMessage = new LocalMessage();
		$localMessage->setId(100);

		$this->sendLocks->expects(self::exactly(2))
			->method('get')
			->with('100')
			->willReturnOnConsecutiveCalls(1, null);

		self::assertTrue($this->chain->isSending($localMessage));
		self::assertFalse($this->chain->isSending($localMessage));
	}
}
