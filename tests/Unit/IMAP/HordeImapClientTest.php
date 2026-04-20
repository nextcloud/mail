<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use OCA\Mail\IMAP\HordeImapClient;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;
use PHPUnit\Framework\MockObject\MockObject;

interface IMemcacheWithTTL extends IMemcache, IMemcacheTTL {
}

/**
 * Testable subclass that stubs out the real IMAP connection.
 */
class TestableHordeImapClient extends HordeImapClient {
	public function __construct() {
		// Skip Horde constructor — we only test rate limiter logic.
	}

	protected function imapLogin() {
		throw new Horde_Imap_Client_Exception(
			'Authentication failed.',
			Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED,
		);
	}
}

class HordeImapClientTest extends TestCase {
	private IMemcacheWithTTL|MockObject $cache;
	private ITimeFactory|MockObject $timeFactory;
	private TestableHordeImapClient $client;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(IMemcacheWithTTL::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->client = new TestableHordeImapClient();
		$this->client->enableRateLimiter($this->cache, 'testhash', $this->timeFactory);
	}

	public function testSetsTtlOnAuthFailure(): void {
		$this->timeFactory->method('getTime')->willReturn(100000);
		$expectedKey = 'testhash9';
		$this->cache->method('get')->with($expectedKey)->willReturn(null);
		$this->cache->expects($this->once())->method('inc')->with($expectedKey);
		$this->cache->expects($this->once())->method('setTTL')->with($expectedKey, 3 * 60 * 60);

		$this->expectException(Horde_Imap_Client_Exception::class);
		$this->client->login();
	}
}
