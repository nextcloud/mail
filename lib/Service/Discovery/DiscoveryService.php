<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use Generator;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Contracts\IDiscoveryConnector;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\IMAP\ImapDiscoveryConnector;
use OCA\Mail\JMAP\JmapDiscoveryConnector;

class DiscoveryService {
	/** @var list<IDiscoveryConnector> */
	private array $connectors;

	public function __construct(
		private AutoconfigLookup $autoconfigLookup,
		private Resolver $resolver,
		ImapDiscoveryConnector $imapConnector,
		JmapDiscoveryConnector $jmapConnector,
	) {
		$this->connectors = [$imapConnector, $jmapConnector];
	}

	/**
	 * Discover the server configurations of all protocols
	 *
	 * The connectors are advanced in turns, one event each, so that a slow
	 * step of one protocol does not hold back the progress of the others.
	 * A complete event is emitted for each protocol as soon as its connector is done.
	 *
	 * @return Generator<int, DiscoveryEvent, mixed, void>
	 */
	public function discover(Horde_Mail_Rfc822_Address $address): Generator {
		$context = new DiscoveryContext($address, $this->autoconfigLookup, $this->resolver);

		$active = [];
		$found = [];
		foreach ($this->connectors as $index => $connector) {
			$active[$index] = $connector->discover($context);
			$found[$index] = false;
		}

		while ($active !== []) {
			foreach ($active as $index => $generator) {
				if (!$generator->valid()) {
					unset($active[$index]);
					yield DiscoveryEvent::complete($this->connectors[$index]->protocol(), $found[$index]);
					continue;
				}

				$event = $generator->current();
				if ($event->getType() === DiscoveryEvent::TYPE_CANDIDATE) {
					$found[$index] = true;
				}
				yield $event;
				$generator->next();
				if (!$generator->valid()) {
					unset($active[$index]);
					yield DiscoveryEvent::complete($this->connectors[$index]->protocol(), $found[$index]);
				}
			}
		}
	}
}
