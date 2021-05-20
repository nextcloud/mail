<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
