<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration;

use ChristophWurst\Nextcloud\Testing\TestCase as Base;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use function class_uses;
use function in_array;

class TestCase extends Base {
	protected function setUp(): void {
		parent::setUp();

		// If it's an IMAP test, we reset the test account automatically
		if (in_array(ImapTest::class, class_uses($this))) {
			/** @var ImapTest $this */
			$this->resetImapAccount();
			$this->disconnectImapAccount();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();

		// If it's an IMAP test, we reset the test account automatically
		if (in_array(ImapTest::class, class_uses($this))) {
			/** @var ImapTest $this */
			$this->disconnectImapAccount();
		}
	}
}
