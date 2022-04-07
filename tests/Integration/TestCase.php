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

namespace OCA\Mail\Tests\Integration;

use OCA\Mail\Tests\Integration\Framework\ImapTest;
use ChristophWurst\Nextcloud\Testing\TestCase as Base;
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
