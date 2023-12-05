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
use OCA\Mail\Http\AttachmentDownloadResponse;

class AttachmentDownloadResponseTest extends TestCase {
	/**
	 * @dataProvider providesResponseData
	 * @param $content
	 * @param $filename
	 * @param $contentType
	 */
	public function testIt($content, $filename, $contentType) {
		$resp = new AttachmentDownloadResponse($content, $filename, $contentType);
		$headers = $resp->getHeaders();
		$this->assertEquals($content, $resp->render());
		$this->assertArrayHasKey('Content-Type', $headers);
		$this->assertEquals($contentType, $headers['Content-Type']);
		$this->assertArrayHasKey('Content-Disposition', $headers);
		$pos = strpos($headers['Content-Disposition'], $filename);
		$this->assertTrue($pos > 0);
	}

	public function providesResponseData() {
		return [
			['1234567890', 'test.txt', 'text/plain']
		];
	}
}
