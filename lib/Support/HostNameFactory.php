<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Support;

use OCP\Util;

/**
 * Class HostNameFactory
 *
 * A simple abstraction over the static `Util::getServerHostName()` to
 * make it mockable in tests.
 */
class HostNameFactory {
	/**
	 * Determine the host's name (without any port numbers)
	 *
	 * @return string
	 */
	public function getHostName(): string {
		return Util::getServerHostName();
	}
}
