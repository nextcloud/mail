<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCA\Mail\Service\TransmissionService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<Event|MessageSentEvent>
 */
class AddressCollectionListener implements IEventListener {
	/** @var IUserPreferences */
	private $preferences;

	/** @var AddressCollector */
	private $collector;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		IUserPreferences $preferences,
		AddressCollector $collector,
		LoggerInterface $logger,
		private TransmissionService $transmissionService,
	) {
		$this->collector = $collector;
		$this->logger = $logger;
		$this->preferences = $preferences;
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof MessageSentEvent)) {
			return;
		}
		if ($this->preferences->getPreference($event->getAccount()->getUserId(), 'collect-data', 'true') !== 'true') {
			$this->logger->debug('Not collecting email addresses because the user opted out');
			return;
		}

		// Non-essential feature, hence we catch all possible errors
		try {
			$message = $event->getLocalMessage();
			$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
			$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
			$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);

			$addresses = $to->merge($cc)->merge($bcc);

			$this->collector->addAddresses($event->getAccount()->getUserId(), $addresses);
		} catch (Throwable $e) {
			$this->logger->warning('Error while collecting mail addresses: ' . $e, [
				'exception' => $e,
			]);
		}
	}
}
