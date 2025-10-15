<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
