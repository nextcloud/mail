<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use DateTimeImmutable;
use Exception;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCA\Mail\Service\OutOfOfficeService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|OutOfOfficeStartedEvent|OutOfOfficeEndedEvent|OutOfOfficeScheduledEvent|OutOfOfficeChangedEvent|OutOfOfficeClearedEvent>
 */
class OutOfOfficeListener implements IEventListener {
	public function __construct(
		private AccountService $accountService,
		private OutOfOfficeService $outOfOfficeService,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof OutOfOfficeStartedEvent)
			&& !($event instanceof OutOfOfficeEndedEvent)
			&& !($event instanceof OutOfOfficeScheduledEvent)
			&& !($event instanceof OutOfOfficeChangedEvent)
			&& !($event instanceof OutOfOfficeClearedEvent)
		) {
			return;
		}

		$eventData = $event->getData();

		// We could simply enable the out-of-office responder in any case as it has its own date
		// check. However, this way it is only enabled when really necessary and the second date
		// check inside the sieve script acts as a redundancy.
		$now = $this->timeFactory->getTime();
		$enabled = $now >= $eventData->getStartDate() && $now < $eventData->getEndDate();
		if ($event instanceof OutOfOfficeStartedEvent) {
			$enabled = true;
		} elseif (($event instanceof OutOfOfficeClearedEvent) || ($event instanceof OutOfOfficeEndedEvent)) {
			$enabled = false;
		}

		$accounts = $this->accountService->findByUserId($event->getData()->getUser()->getUID());
		foreach ($accounts as $account) {
			if (!$account->getMailAccount()->getOutOfOfficeFollowsSystem()) {
				continue;
			}

			$state = new OutOfOfficeState(
				$enabled,
				new DateTimeImmutable('@' . $eventData->getStartDate()),
				new DateTimeImmutable('@' . $eventData->getEndDate()),
				'Re: ${subject}',
				$eventData->getMessage(),
			);
			try {
				$this->outOfOfficeService->update($account->getMailAccount(), $state);
			} catch (Exception $e) {
				$this->logger->error('Failed to apply out-of-office sieve script: ' . $e->getMessage(), [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
				]);
			}
		}
	}
}
