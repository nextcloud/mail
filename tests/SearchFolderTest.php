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

namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\Account;
use OCA\Mail\SearchFolder;
use PHPUnit_Framework_MockObject_MockObject;

class SearchFolderTest extends TestCase {

	/** @var Account|PHPUnit_Framework_MockObject_MockObject */
	private $account;

	/** @var Horde_Imap_Client_Mailbox|PHPUnit_Framework_MockObject_MockObject */
	private $mailbox;

	/** @var SearchFolder */
	private $folder;

	protected function setUp() {
		parent::setUp();

		$this->account = $this->createMock(Account::class);
		$this->mailbox = $this->createMock(Horde_Imap_Client_Mailbox::class);

		$this->folder = new SearchFolder($this->account, $this->mailbox, [], ',');
	}

	public function testGetMailbox() {
		$this->mailbox->expects($this->once())
			->method('__get')
			->with($this->equalTo('utf8'))
			->willReturn('INBOX');

		$this->assertSame('INBOX/FLAGGED', $this->folder->getMailbox());
	}

	public function testIsSearchable() {
		$this->assertFalse($this->folder->isSearchable());
	}

}
