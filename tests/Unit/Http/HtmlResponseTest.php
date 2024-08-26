<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Http;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Http\HtmlResponse;

class HtmlResponseTest extends TestCase {
	/**
	 * @dataProvider providesResponseData
	 * @param $content
	 * @param $filename
	 * @param $contentType
	 */
	public function testIt($content) {
		$nonce = 'abc123';
		$scriptUrl = 'next.cloud/script.js';
		$plainResp = HtmlResponse::plain($content);
		$richResp = HtmlResponse::withResizer($content, $nonce, $scriptUrl);

		$this->assertStringContainsString("<script nonce=\"$nonce\" src=\"$scriptUrl\"></script>", $richResp->render());
		$this->assertEquals($content, $plainResp->render());
	}

	public function providesResponseData() {
		return [
			['1234567890']
		];
	}
}
