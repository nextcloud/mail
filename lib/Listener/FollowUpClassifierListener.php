<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use DateInterval;
use DateTimeImmutable;
use OCA\Mail\BackgroundJob\FollowUpClassifierJob;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\TextProcessing\FreePromptTaskType;

/**
 * @template-implements IEventListener<Event|NewMessagesSynchronized>
 */
class FollowUpClassifierListener implements IEventListener {

	public function __construct(
		private IJobList $jobList,
		private AiIntegrationsService $aiService,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		if (!$event->getMailbox()->isSpecialUse('sent')
			&& $event->getAccount()->getMailAccount()->getSentMailboxId() !== $event->getMailbox()->getId()
		) {
			return;
		}

		if (!$this->aiService->isLlmProcessingEnabled()) {
			return;
		}

		if (!$this->aiService->isLlmAvailable(FreePromptTaskType::class)) {
			return;
		}

		// Do not process emails older than 14D to save some processing power
		$notBefore = (new DateTimeImmutable('now'))
			->sub(new DateInterval('P14D'));
		$userId = $event->getAccount()->getUserId();
		foreach ($event->getMessages() as $message) {
			if ($message->getSentAt() < $notBefore->getTimestamp()) {
				continue;
			}

			$isTagged = false;
			foreach ($message->getTags() as $tag) {
				if ($tag->getImapLabel() === '$follow_up') {
					$isTagged = true;
					break;
				}
			}
			if ($isTagged) {
				continue;
			}

			$jobArguments = [
				FollowUpClassifierJob::PARAM_MESSAGE_ID => $message->getMessageId(),
				FollowUpClassifierJob::PARAM_MAILBOX_ID => $message->getMailboxId(),
				FollowUpClassifierJob::PARAM_USER_ID => $userId,
			];
			// TODO: only use scheduleAfter() once we support >= 28.0.0
			if (method_exists(IJobList::class, 'scheduleAfter')) {
				// Delay job a bit because there might be some replies until then and we might be able
				// to skip the expensive LLM task
				$timestamp = (new DateTimeImmutable('@' . $message->getSentAt()))
					->add(new DateInterval('P3DT12H'))
					->getTimestamp();
				$this->jobList->scheduleAfter(
					FollowUpClassifierJob::class,
					$timestamp,
					$jobArguments,
				);
			} else {
				$this->jobList->add(FollowUpClassifierJob::class, $jobArguments);
			}
		}
	}
}
