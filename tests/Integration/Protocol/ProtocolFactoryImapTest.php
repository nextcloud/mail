<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\Server;

class ProtocolFactoryImapTest extends TestCase {
	use ImapTestAccount;

	private ProtocolFactory $protocolFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = Server::get(ProtocolFactory::class);
	}

	public function testImapClientConnection(): void {
		$account = new Account($this->createTestAccount());

		$client = $this->protocolFactory->imapClient($account);

		$this->assertInstanceOf(Horde_Imap_Client_Socket::class, $client);
		$client->login();
		$client->logout();
	}
}
