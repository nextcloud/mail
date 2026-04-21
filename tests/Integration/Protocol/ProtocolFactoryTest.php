<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use JmapClient\Client as JmapClient;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCA\Mail\Tests\Integration\Framework\JmapTestAccount;
use OCP\Server;

class ProtocolFactoryTest extends TestCase {
	use ImapTestAccount;
	use JmapTestAccount;

	private ProtocolFactory $protocolFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = Server::get(ProtocolFactory::class);
	}

	public function testImapClientConnection(): void {
		$account = $this->createTestAccount();

		$client = $this->protocolFactory->imapClient($account);

		$this->assertInstanceOf(Horde_Imap_Client_Socket::class, $client);
		$client->login();
		$client->logout();
	}

	public function testJmapClientConnection(): void {
		$account = $this->createTestAccount();

		$client = $this->protocolFactory->jmapClient($account);

		$this->assertInstanceOf(JmapClient::class, $client);

		$session = $client->connect();

		$this->assertTrue($client->sessionStatus(), 'JMAP session should be established');
		$this->assertNotEmpty($session->username(), 'Session should report a username');
		$this->assertNotEmpty($session->commandUrl(), 'Session should provide an API URL');
	}
}
