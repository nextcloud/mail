<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Provisioning;

/**
 * @covers \OCA\Mail\Db\Provisioning
 */
class ProvisioningTest extends TestCase {

	public function testJsonSerialize(): void {
		$provisioning = new Provisioning();

		$data = $provisioning->jsonSerialize();

		self::assertArrayHasKey('masterPasswordEnabled', $data);
	}

}
