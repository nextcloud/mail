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

namespace OCA\Mail\Tests\Http;

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

		$resp = new HtmlResponse($content);
		$this->assertEquals($content, $resp->render());
	}

	public function providesResponseData() {
		return [
				['1234567890']
		];
	}

}
