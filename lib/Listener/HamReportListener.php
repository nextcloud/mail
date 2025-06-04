<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Service\AntiSpamService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|MessageFlaggedEvent>
 */
class HamReportListener implements IEventListener {
	/** @var LoggerInterface */
	private $logger;

	/** @var AntiSpamService */
	private $antiSpamService;

	public function __construct(LoggerInterface $logger,
		AntiSpamService $antiSpamService) {
		$this->logger = $logger;
		$this->antiSpamService = $antiSpamService;
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof MessageFlaggedEvent || $event->getFlag() !== '$notjunk') {
			return;
		}

		if (!$event->isSet()) {
			return;
		}

		// Send message to reporting service
		try {
			$this->antiSpamService->sendReportEmail($event->getAccount(), $event->getMailbox(), $event->getUid(), $event->getFlag());
		} catch (\Throwable $e) {
			$this->logger->error('Could not send spam report: ' . $e->getMessage(), ['exception' => $e]);
		}
	}
}
