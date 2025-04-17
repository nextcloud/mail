<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\LazyHordeImapClient;
use PHPUnit\Framework\MockObject\MockObject;

class LazyHordeImapClientTest extends TestCase {
	private IMAPClientFactory&MockObject $clientFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
	}

	public function testGetClient(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->clientFactory->expects(self::once())
			->method('getClient')
			->with()
			->willReturn($client);

		$lazyClient = new LazyHordeImapClient($this->clientFactory, $account, true);

		// Should always return the same (cached) client
		$this->assertEquals($client, $lazyClient->getClient());
		$this->assertEquals($client, $lazyClient->getClient());
	}

	public function testLogout(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects(self::once())
			->method('logout');

		$this->clientFactory->expects(self::once())
			->method('getClient')
			->with($account, true)
			->willReturn($client);

		$lazyClient = new LazyHordeImapClient($this->clientFactory, $account, true);
		$lazyClient->getClient();
		$lazyClient->logout();
	}

	public function testLogoutNoClient(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->expects(self::never())
			->method('logout');

		$this->clientFactory->expects(self::never())
			->method('getClient');

		$lazyClient = new LazyHordeImapClient($this->clientFactory, $account, true);
		$lazyClient->logout();
	}
}
