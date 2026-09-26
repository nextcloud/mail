<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<Event|UserChangedEvent>
 */
class UserEnabledDisabledListener implements IEventListener {
	public function __construct(
		private AccountService $accountService,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent) || $event->getFeature() !== 'enabled') {
			return;
		}

		$enabled = (bool)$event->getValue();
		$uid = $event->getUser()->getUID();
		try {
			foreach ($this->accountService->findByUserId($uid) as $account) {
				if ($enabled) {
					$this->accountService->scheduleBackgroundJobs($account->getId());
				} else {
					$this->accountService->removeBackgroundJobs($account->getId());
				}
			}
		} catch (Throwable $e) {
			$this->logger->error('Could not update Mail background jobs after user {uid} was ' . ($enabled ? 'enabled' : 'disabled'), [
				'uid' => $uid,
				'exception' => $e,
			]);
		}
	}
}
