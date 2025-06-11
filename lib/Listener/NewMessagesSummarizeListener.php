<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class NewMessagesSummarizeListener implements IEventListener {

	public function __construct(
		private LoggerInterface $logger,
		private AiIntegrationsService $aiService,
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($this->appConfig->getAppValueBool('llm_processing', false) === false) {
			return;
		}
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		try {
			$this->aiService->summarizeMessages(
				$event->getAccount(),
				$event->getMessages(),
			);
		} catch (ServiceException $e) {
			$this->logger->error('Could not initiate a message summarize task(s): ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
