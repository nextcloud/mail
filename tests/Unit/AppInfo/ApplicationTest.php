<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Tests\Unit\AppInfo;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;

class ApplicationTest extends TestCase {
	public function testConstrucor() {
		// Not really a test – it's just about code coverage
		new Application();

		$this->addToAssertionCount(1);
	}
}
