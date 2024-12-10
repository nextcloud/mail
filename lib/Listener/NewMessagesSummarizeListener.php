<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|NewMessagesSummarizeListener>
 */
class NewMessagesSummarizeListener implements IEventListener {

	public function __construct(
		protected LoggerInterface $logger,
		protected IMAPClientFactory $imapFactory,
		protected AiIntegrationsService $aiService,
		protected IMailManager $mailManager
	) { }

	public function handle(Event $event): void {

		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		try {
			$this->aiService->summarizeMessages(
				$event->getAccount(),
				$event->getMessages(),
			);
		} catch (ServiceException $e) {
			$this->logger->error('Could not classify incoming message importance: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
