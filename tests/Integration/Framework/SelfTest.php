<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

use Horde_Imap_Client_Data_Namespace;
use OCA\Mail\Tests\Integration\TestCase;

/**
 * Tests the IMAP test framework functionality
 */
final class SelfTest extends TestCase {
	use ImapTest;

	public function testResetAccount(): void {
		$this->assertCount(4, $this->getMailboxes());
		$this->createImapMailbox('folder1');
		$this->assertCount(5, $this->getMailboxes());
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->assertCount(4, $this->getMailboxes());
	}

	public function testResetLeavesSharedNamespaceAlone(): void {
		$client = $this->getTestClient();
		$sharedMailboxes = [];
		foreach ($client->getNamespaces([], ['ob_return' => true]) as $namespace) {
			if ($namespace->type === Horde_Imap_Client_Data_Namespace::NS_SHARED && $namespace->name !== '') {
				$sharedMailboxes = [...$sharedMailboxes, ...array_keys($client->listMailboxes($namespace->name . '*'))];
			}
		}
		if ($sharedMailboxes === []) {
			$this->markTestSkipped('The IMAP server has no shared mailboxes');
		}

		$this->resetImapAccount();

		foreach ($sharedMailboxes as $mailbox) {
			$this->assertNotEmpty($client->listMailboxes($mailbox), "shared mailbox <$mailbox> was deleted");
			$this->assertArrayNotHasKey($mailbox, $this->getMailboxes());
		}
	}

	public function testMessageCapabilities(): void {
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
