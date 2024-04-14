<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateInterval;
use DateTimeImmutable;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\FollowUpClassifierJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Listener\FollowUpClassifierListener;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\BackgroundJob\IJobList;
use OCP\TextProcessing\FreePromptTaskType;

class FollowUpClassifierListenerTest extends TestCase {
	private FollowUpClassifierListener $listener;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var AiIntegrationsService|MockObject */
	private $aiService;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->aiService = $this->createMock(AiIntegrationsService::class);

		$this->listener = new FollowUpClassifierListener(
			$this->jobList,
			$this->aiService,
		);
	}

	public function testHandle(): void {
		$sentAt = new DateTimeImmutable('now');
		$scheduleAfterTimestamp = $sentAt->add(new DateInterval('P3DT12H'))->getTimestamp();
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('user');
		$mailAccount->setSentMailboxId(200);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setSpecialUse('["sent"]');
		$message = new Message();
		$message->setMessageId('<message1@foo.bar>');
		$message->setThreadRootId('<message1@foo.bar>');
		$message->setSentAt($sentAt->getTimestamp());
		$message->setMailboxId(200);
		$message->setTags([]);
		$event = new NewMessagesSynchronized($account, $mailbox, [$message]);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->aiService->expects(self::once())
			->method('isLlmAvailable')
			->with(FreePromptTaskType::class)
			->willReturn(true);
		// TODO: only assert scheduleAfter() once we support >= 28.0.0
		if (method_exists(IJobList::class, 'scheduleAfter')) {
			$this->jobList->expects(self::once())
				->method('scheduleAfter')
				->with(FollowUpClassifierJob::class, $scheduleAfterTimestamp, [
					FollowUpClassifierJob::PARAM_MESSAGE_ID => '<message1@foo.bar>',
					FollowUpClassifierJob::PARAM_MAILBOX_ID => 200,
					FollowUpClassifierJob::PARAM_USER_ID => 'user',
				]);
			$this->jobList->expects(self::never())
				->method('add');
		} else {
			$this->jobList->expects(self::once())
				->method('add')
				->with(FollowUpClassifierJob::class, [
					FollowUpClassifierJob::PARAM_MESSAGE_ID => '<message1@foo.bar>',
					FollowUpClassifierJob::PARAM_MAILBOX_ID => 200,
					FollowUpClassifierJob::PARAM_USER_ID => 'user',
				]);
		}

		$this->listener->handle($event);
	}

	public function testHandleLlmProcessingDisabled(): void {
		$sentAt = new DateTimeImmutable('now');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('user');
		$mailAccount->setSentMailboxId(200);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setSpecialUse('["sent"]');
		$message = new Message();
		$message->setMessageId('<message1@foo.bar>');
		$message->setThreadRootId('<message1@foo.bar>');
		$message->setSentAt($sentAt->getTimestamp());
		$message->setMailboxId(200);
		$message->setTags([]);
		$event = new NewMessagesSynchronized($account, $mailbox, [$message]);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(false);
		$this->aiService->expects(self::never())
			->method('isLlmAvailable');
		// TODO: only assert scheduleAfter() once we support >= 28.0.0
		if (method_exists(IJobList::class, 'scheduleAfter')) {
			$this->jobList->expects(self::never())
				->method('scheduleAfter');
		}
		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle($event);
	}

	public function testHandleLlmTaskUnavailable(): void {
		$sentAt = new DateTimeImmutable('now');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('user');
		$mailAccount->setSentMailboxId(200);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setSpecialUse('["sent"]');
		$message = new Message();
		$message->setMessageId('<message1@foo.bar>');
		$message->setThreadRootId('<message1@foo.bar>');
		$message->setSentAt($sentAt->getTimestamp());
		$message->setMailboxId(200);
		$message->setTags([]);
		$event = new NewMessagesSynchronized($account, $mailbox, [$message]);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->aiService->expects(self::once())
			->method('isLlmAvailable')
			->with(FreePromptTaskType::class)
			->willReturn(false);
		// TODO: only assert scheduleAfter() once we support >= 28.0.0
		if (method_exists(IJobList::class, 'scheduleAfter')) {
			$this->jobList->expects(self::never())
				->method('scheduleAfter');
		}
		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle($event);
	}

	public function testHandleSkipTagged(): void {
		$sentAt = new DateTimeImmutable('now');
		$followUpTag = new Tag();
		$followUpTag->setImapLabel('$follow_up');
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('user');
		$mailAccount->setSentMailboxId(200);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setSpecialUse('["sent"]');
		$message = new Message();
		$message->setMessageId('<message1@foo.bar>');
		$message->setThreadRootId('<message1@foo.bar>');
		$message->setSentAt($sentAt->getTimestamp());
		$message->setMailboxId(200);
		$message->setTags([$followUpTag]);
		$event = new NewMessagesSynchronized($account, $mailbox, [$message]);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->aiService->expects(self::once())
			->method('isLlmAvailable')
			->with(FreePromptTaskType::class)
			->willReturn(true);
		// TODO: only assert scheduleAfter() once we support >= 28.0.0
		if (method_exists(IJobList::class, 'scheduleAfter')) {
			$this->jobList->expects(self::never())
				->method('scheduleAfter');
		}
		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle($event);
	}

	public function testHandleSkipOld(): void {
		$sentAt = (new DateTimeImmutable('now'))
			->sub(new \DateInterval('P14DT1S'));
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('user');
		$mailAccount->setSentMailboxId(200);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(200);
		$mailbox->setSpecialUse('["sent"]');
		$message = new Message();
		$message->setMessageId('<message1@foo.bar>');
		$message->setThreadRootId('<message1@foo.bar>');
		$message->setSentAt($sentAt->getTimestamp());
		$message->setMailboxId(200);
		$message->setTags([]);
		$event = new NewMessagesSynchronized($account, $mailbox, [$message]);

		$this->aiService->expects(self::once())
			->method('isLlmProcessingEnabled')
			->willReturn(true);
		$this->aiService->expects(self::once())
			->method('isLlmAvailable')
			->with(FreePromptTaskType::class)
			->willReturn(true);
		// TODO: only assert scheduleAfter() once we support >= 28.0.0
		if (method_exists(IJobList::class, 'scheduleAfter')) {
			$this->jobList->expects(self::never())
				->method('scheduleAfter');
		}
		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle($event);
	}

}
