<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\Sync\Response;

class ResponseTest extends TestCase {
	public function testJsonSerialize() {
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessages = [];
		$response = new Response($newMessages, $changedMessages, $vanishedMessages);
		$expected = [
			'newMessages' => [],
			'changedMessages' => [],
			'vanishedMessages' => [],
			'stats' => null,
		];

		$json = $response->jsonSerialize();

		$this->assertEquals($expected, $json);
	}
}
