<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
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

	public function __construct(IUserPreferences $preferences,
								AddressCollector $collector,
								LoggerInterface $logger) {
		$this->collector = $collector;
		$this->logger = $logger;
		$this->preferences = $preferences;
	}

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
			$message = $event->getMessage();
			$addresses = $message->getTo()
				->merge($message->getCC())
				->merge($message->getBCC());

			$this->collector->addAddresses($event->getAccount()->getUserId(), $addresses);
		} catch (Throwable $e) {
			$this->logger->warning('Error while collecting mail addresses: ' . $e, [
				'exception' => $e,
			]);
		}
	}
}
