<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Tests\Integration\Framework\JmapTestAccount;
use OCP\Server;

class ProtocolFactoryJmapTest extends TestCase {
	use JmapTestAccount;

	private ProtocolFactory $protocolFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = Server::get(ProtocolFactory::class);
	}

	public function testJmapClientConnection(): void {
		$account = new Account($this->createTestAccount());

		$client = $this->protocolFactory->jmapClient($account);

		$this->assertInstanceOf(JmapClient::class, $client);

		$session = $client->connect();

		$this->assertTrue($client->sessionStatus(), 'JMAP session should be established');
		$this->assertNotEmpty($session->username(), 'Session should report a username');
		$this->assertNotEmpty($session->commandUrl(), 'Session should provide an API URL');
	}
}
