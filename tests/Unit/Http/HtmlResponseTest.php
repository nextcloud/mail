<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
		$nonce = "abc123";
		$scriptUrl = "next.cloud/script.js";
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
