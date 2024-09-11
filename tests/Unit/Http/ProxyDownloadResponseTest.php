<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Http;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Http\ProxyDownloadResponse;

class ProxyDownloadResponseTest extends TestCase {
	/**
	 * @dataProvider providesResponseData
	 * @param $content
	 * @param $filename
	 * @param $contentType
	 */
	public function testIt($content, $filename, $contentType) {
		$resp = new ProxyDownloadResponse($content, $filename, $contentType);
		$headers = $resp->getHeaders();
		$this->assertEquals($content, $resp->render());
		$this->assertArrayHasKey('Content-Type', $headers);
		$this->assertEquals($contentType, $headers['Content-Type']);
		$this->assertArrayHasKey('Content-Disposition', $headers);
		$pos = strpos($headers['Content-Disposition'], (string)$filename);
		$this->assertTrue($pos > 0);
	}

	public function providesResponseData() {
		return [
			['1234567890', 'test.txt', 'text/plain']
		];
	}
}
