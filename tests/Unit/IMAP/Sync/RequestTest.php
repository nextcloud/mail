<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\Sync\Request;

class RequestTest extends TestCase {

	private string $mailbox;
	private string $syncToken;
	private Request $request;
	private string $requestId;

	protected function setUp(): void {
		parent::setUp();

		$this->mailbox = 'inbox';
		$this->syncToken = 'ab123';
		$this->requestId = 'abcdef';

		$this->request = new Request($this->requestId, $this->mailbox, $this->syncToken, []);
	}

	public function testGetId() {
		$this->assertEquals($this->requestId, $this->request->getId());
	}

	public function testGetMailbox() {
		$this->assertEquals($this->mailbox, $this->request->getMailbox());
	}

	public function testGetSyncToken() {
		$this->assertEquals($this->syncToken, $this->request->getToken());
	}
}
