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
use OCA\Mail\IMAP\Sync\Request;

class RequestTest extends TestCase {
	/** @var string */
	private $mailbox;

	/** @var string */
	private $syncToken;

	/** @var Request */
	private $request;

	protected function setUp(): void {
		parent::setUp();

		$this->mailbox = 'inbox';
		$this->syncToken = 'ab123';

		$this->request = new Request($this->mailbox, $this->syncToken, []);
	}

	public function testGetMailbox() {
		$this->assertEquals($this->mailbox, $this->request->getMailbox());
	}

	public function testGetSyncToken() {
		$this->assertEquals($this->syncToken, $this->request->getToken());
	}
}
