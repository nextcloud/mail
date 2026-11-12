<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use Generator;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;

interface IDiscoveryConnector {
	/**
	 * @return MailAccount::PROTOCOL_*
	 */
	public function protocol(): string;

	/**
	 * Discover server configurations for the context's address
	 *
	 * Yields a probe event before each slow step so the discovery of
	 * other protocols can proceed in between.
	 *
	 * @return Generator<int, DiscoveryEvent, mixed, void>
	 */
	public function discover(DiscoveryContext $context): Generator;
}
