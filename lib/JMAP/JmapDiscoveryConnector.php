<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use Generator;
use OCA\Mail\Contracts\IDiscoveryConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use Override;

/**
 * Discovers the JMAP server of an address, stopping at the first step that finds it
 */
class JmapDiscoveryConnector implements IDiscoveryConnector {
	private const SERVICE = '_jmap._tcp';
	private const WELL_KNOWN_PATH = '/.well-known/jmap';
	private const HTTPS_PORT = 443;

	public function __construct(
		private JmapSessionProbe $probe,
	) {
	}

	#[Override]
	public function protocol(): string {
		return MailAccount::PROTOCOL_JMAP;
	}

	#[Override]
	public function discover(DiscoveryContext $context): Generator {
		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_AUTOCONFIG, DiscoveryEvent::STATUS_RUNNING);
		$jmap = $context->autoconfig()?->getJmap();
		yield from $this->report(DiscoveryEvent::SOURCE_AUTOCONFIG, $jmap);
		if ($jmap !== null) {
			return;
		}

		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_SRV, DiscoveryEvent::STATUS_RUNNING);
		$jmap = null;
		$record = $context->srv(self::SERVICE)[0] ?? null;
		if ($record !== null) {
			$authority = $record['port'] === self::HTTPS_PORT ? $record['target'] : $record['target'] . ':' . $record['port'];
			$jmap = $this->probe->probe('https://' . $authority . self::WELL_KNOWN_PATH, $context->getEmail());
		}
		yield from $this->report(DiscoveryEvent::SOURCE_SRV, $jmap);
		if ($jmap !== null) {
			return;
		}

		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_WELL_KNOWN, DiscoveryEvent::STATUS_RUNNING);
		$jmap = $this->probe->probe('https://' . $context->getDomain() . self::WELL_KNOWN_PATH, $context->getEmail());
		yield from $this->report(DiscoveryEvent::SOURCE_WELL_KNOWN, $jmap);
	}

	/**
	 * @param DiscoveryEvent::SOURCE_* $source
	 * @return Generator<int, DiscoveryEvent, mixed, void>
	 */
	private function report(string $source, ?JmapServerConfiguration $jmap): Generator {
		if ($jmap !== null) {
			yield DiscoveryEvent::candidate($this->protocol(), $source, ['jmap' => $jmap]);
		}
		yield DiscoveryEvent::probe($this->protocol(), $source, $jmap !== null ? DiscoveryEvent::STATUS_FOUND : DiscoveryEvent::STATUS_NONE);
	}
}
