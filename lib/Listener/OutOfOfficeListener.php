<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		if (($event instanceof OutOfOfficeClearedEvent) || ($event instanceof OutOfOfficeEndedEvent)) {
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
				$eventData->getShortMessage(),
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
