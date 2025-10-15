<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Http;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Http\AvatarDownloadResponse;

class AvatarDownloadResponseTest extends TestCase {
	public function testResponse() {
		$response = new AvatarDownloadResponse('data');

		$this->assertEquals('application/octet-stream', $response->getHeaders()['Content-Type']);
		$this->assertEquals('data', $response->render());
	}
}
