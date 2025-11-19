<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Exception\ClientException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @template-implements IEventListener<Event|UserDeletedEvent>
 */
class UserDeletedListener implements IEventListener {
	public function __construct(
		private readonly \OCA\Mail\Service\AccountService $accountService,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			// Unrelated
			return;
		}

		$user = $event->getUser();
		foreach ($this->accountService->findByUserId($user->getUID()) as $account) {
			try {
				$this->accountService->delete(
					$user->getUID(),
					$account->getId()
				);
			} catch (ClientException $e) {
				$this->logger->error('Could not delete user\'s Mail account: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}
}
