<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Model;

use OCA\Mail\Model\EventData;
use Test\TestCase;

class EventDataTest extends TestCase {

	public function testAll(): void {
		$data = new EventData('sum', 'des');

		self::assertSame('sum', $data->getSummary());
		self::assertSame('des', $data->getDescription());
	}

}
