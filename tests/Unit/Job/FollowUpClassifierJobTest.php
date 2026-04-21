<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Job;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\FollowUpClassifierJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class FollowUpClassifierJobTest extends TestCase {

	private FollowUpClassifierJob $job;

	/** @var ITimeFactory|MockObject */
	private $time;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var AiIntegrationsService|MockObject */
	private $aiService;

	/** @var ThreadMapper|MockObject */
	private $threadMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->aiService = $this->createMock(AiIntegrationsService::class);
		$this->threadMapper = $this->createMock(ThreadMapper::class);

		$this->job = new FollowUpClassifierJob(
			$this->time,
			$this->logger,
			$this->accountService,
			$this->mailManager,
			$this->aiService,
			$this->threadMapper,
		);
	}

	public function testRun(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$messages = [$message];
		$tag = new Tag();
		$tag->setImapLabel('$follow_up');

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::once())
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([]);
		$this->aiService->expects(self::once())
			->method('requiresFollowUp')
			->with($account, $mailbox, $message, 'user')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('createTag')
			->with('Follow up', '#d77000', 'user')
			->willReturn($tag);
		$this->mailManager->expects(self::once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, true, $message);

		$this->job->run($argument);
	}

	public function testRunLlmProcessingDisabled(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(false);
		$this->mailManager->expects(self::never())
			->method('getMailbox');
		$this->accountService->expects(self::never())
			->method('find');
		$this->mailManager->expects(self::never())
			->method('getMessagesByMessageId');
		$this->threadMapper->expects(self::never())
			->method('findNewerMessageIdsInThread');
		$this->aiService->expects(self::never())
			->method('requiresFollowUp');
		$this->mailManager->expects(self::never())
			->method('createTag');
		$this->mailManager->expects(self::never())
			->method('tagMessages');

		$this->job->run($argument);
	}

	public function testRunNoMessages(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$messages = [];

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::never())
			->method('findNewerMessageIdsInThread');
		$this->aiService->expects(self::never())
			->method('requiresFollowUp');
		$this->mailManager->expects(self::never())
			->method('createTag');
		$this->mailManager->expects(self::never())
			->method('tagMessages');

		$this->job->run($argument);
	}

	public function testRunMultipleMessages(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$message2 = new Message();
		$message2->setMailboxId(200);
		$message2->setMessageId('<message2@foo.bar>');
		$messages = [$message, $message2];
		$tag = new Tag();
		$tag->setImapLabel('$follow_up');

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::once())
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([]);
		$this->aiService->expects(self::once())
			->method('requiresFollowUp')
			->with($account, $mailbox, $message, 'user')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('createTag')
			->with('Follow up', '#d77000', 'user')
			->willReturn($tag);
		$this->mailManager->expects(self::once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, true, $message);

		$this->job->run($argument);
	}

	public function testRunCreateTag(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$messages = [$message];
		$tag = new Tag();
		$tag->setImapLabel('$follow_up');

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::once())
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([]);
		$this->aiService->expects(self::once())
			->method('requiresFollowUp')
			->with($account, $mailbox, $message, 'user')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('createTag')
			->with('Follow up', '#d77000', 'user')
			->willReturn($tag);
		$this->mailManager->expects(self::once())
			->method('tagMessages')
			->with($account, $mailbox, $tag, true, $message);

		$this->job->run($argument);
	}

	public function testRunNoFollowUp(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$messages = [$message];
		$tag = new Tag();
		$tag->setImapLabel('$follow_up');

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::once())
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([]);
		$this->aiService->expects(self::once())
			->method('requiresFollowUp')
			->with($account, $mailbox, $message, 'user')
			->willReturn(false);
		$this->mailManager->expects(self::never())
			->method('createTag');
		$this->mailManager->expects(self::never())
			->method('tagMessages');

		$this->job->run($argument);
	}

	public function testRunFollowedUp(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$messages = [$message];
		$tag = new Tag();
		$tag->setImapLabel('$follow_up');

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn($messages);
		$this->threadMapper->expects(self::once())
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([201]);
		$this->aiService->expects(self::never())
			->method('requiresFollowUp');
		$this->mailManager->expects(self::never())
			->method('createTag');
		$this->mailManager->expects(self::never())
			->method('tagMessages');

		$this->job->run($argument);
	}

	public function testRunServiceFailure(): void {
		$argument = [
			'messageId' => '<message1@foo.bar>',
			'mailboxId' => 200,
			'userId' => 'user',
		];
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setAccountId(100);
		$mailbox->setName('sent');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setMailboxId(200);
		$message->setMessageId('<message1@foo.bar>');
		$exception = new ServiceException('AI task processing failed');

		$this->aiService->method('isLlmProcessingEnabled')->willReturn(true);
		$this->mailManager
			->method('getMailbox')
			->with('user', 200)
			->willReturn($mailbox);
		$this->accountService
			->method('find')
			->with('user', 100)
			->willReturn($account);
		$this->mailManager
			->method('getMessagesByMessageId')
			->with($account, '<message1@foo.bar>')
			->willReturn([$message]);
		$this->threadMapper
			->method('findNewerMessageIdsInThread')
			->with(100, $message)
			->willReturn([]);
		$this->aiService
			->expects(self::once())
			->method('requiresFollowUp')
			->with($account, $mailbox, $message, 'user')
			->willThrowException($exception);
		$this->logger
			->expects(self::once())
			->method('error')
			->with('Failed to classify message for follow-up: AI task processing failed', ['exception' => $exception]);
		$this->mailManager->expects(self::never())->method('createTag');
		$this->mailManager->expects(self::never())->method('tagMessages');

		$this->job->run($argument);
	}
}
