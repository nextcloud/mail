<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\AutoConfig\ServerConfiguration;

class ServerConfigurationTest extends TestCase {
	public function testCreateObject(): void {
		$cfg = new ServerConfiguration(
			'username',
			'host',
			123,
			'tls',
		);
	}
}
