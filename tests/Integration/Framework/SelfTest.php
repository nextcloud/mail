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

namespace OCA\Mail\Tests\Integration\Framework;

use OCA\Mail\Tests\Integration\TestCase;

/**
 * Tests the IMAP test framework functionality
 */
class SelfTest extends TestCase {
	use ImapTest;

	public function testResetAccount() {
		$this->assertCount(4, $this->getMailboxes());
		$this->createImapMailbox('folder1');
		$this->assertCount(5, $this->getMailboxes());
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->assertCount(4, $this->getMailboxes());
	}

	public function testMessageCapabilities() {
		$mb = $this->getMessageBuilder();
		$message = $mb->to('fritz@phantom.at')
			->from('tom@turbo.at')
			->subject('hello')
			->body('hi!')
			->finish();

		$this->assertMessageCount(0, 'INBOX');
		$id = $this->saveMessage('INBOX', $message);
		$this->assertMessageCount(1, 'INBOX');
		$this->assertIsInt($id);
	}
}
