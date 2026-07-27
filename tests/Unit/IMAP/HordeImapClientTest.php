<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use OC\Memcache\ArrayCache;
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
	/** @var list<'ok'|'fail'> */
	public array $outcomes = [];
	public int $realAttempts = 0;

	public function __construct() {
		// Skip Horde constructor — we only test rate limiter logic.
	}

	public function doLogin() {
		return $this->_login();
	}

	protected function imapLogin() {
		$this->realAttempts++;
		$outcome = array_shift($this->outcomes) ?? 'fail';
		if ($outcome === 'ok') {
			return true;
		}
		throw new Horde_Imap_Client_Exception(
			$outcome === 'denied' ? 'Mail server denied authentication.' : 'Authentication failed.',
			Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED,
		);
	}
}

class HordeImapClientTest extends TestCase {
	private ArrayCache $cache;
	private ITimeFactory|MockObject $timeFactory;
	private TestableHordeImapClient $client;
	private int $now = 100000;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = new ArrayCache('horde_test');
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')
			->willReturnCallback(fn () => $this->now);

		$this->client = new TestableHordeImapClient();
		$this->client->enableRateLimiter($this->cache, 'testhash', $this->timeFactory);
	}

	private function loginExpectingAuthFailure(): void {
		try {
			$this->client->doLogin();
			$this->fail('login should have failed with an authentication error');
		} catch (Horde_Imap_Client_Exception $e) {
			$this->assertSame('Authentication failed.', $e->getMessage());
		}
	}

	private function loginExpectingThrottle(): void {
		try {
			$this->client->doLogin();
			$this->fail('login should have been throttled');
		} catch (Horde_Imap_Client_Exception $e) {
			$this->assertSame('Too many auth attempts', $e->getMessage());
		}
	}

	public function testFirstThreeFailuresAreNotThrottled(): void {
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();

		$this->assertSame(3, $this->client->realAttempts);
	}

	public function testFastRetriesEveryThirtySecondsThenSlowTrickle(): void {
		// Arm the throttle with three failures
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();

		// One real attempt is allowed right away (failure #4)…
		$this->loginExpectingAuthFailure();
		$this->assertSame(4, $this->client->realAttempts);

		// …but not a second one within the same 30 seconds
		$this->now += 10;
		$this->loginExpectingThrottle();
		$this->assertSame(4, $this->client->realAttempts);

		// Failures #5 and #6 at 30 second spacing
		$this->now += 20;
		$this->loginExpectingAuthFailure();
		$this->now += 30;
		$this->loginExpectingAuthFailure();
		$this->assertSame(6, $this->client->realAttempts);

		// From now on only one attempt per 5 minutes
		$this->now += 30;
		$this->loginExpectingThrottle();
		$this->now += 240;
		$this->loginExpectingThrottle();
		$this->assertSame(6, $this->client->realAttempts);

		// 300 seconds after the last real attempt the next one is allowed
		$this->now += 30;
		$this->loginExpectingAuthFailure();
		$this->assertSame(7, $this->client->realAttempts);
	}

	public function testSuccessfulLoginResetsTheThrottle(): void {
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();
		$this->loginExpectingAuthFailure();

		// The immediate throttled attempt succeeds (server recovered)
		$this->client->outcomes = ['ok', 'ok', 'ok'];
		$this->assertTrue($this->client->doLogin());

		// State is cleared: subsequent logins are not throttled at all
		$this->assertTrue($this->client->doLogin());
		$this->assertTrue($this->client->doLogin());
		$this->assertSame(6, $this->client->realAttempts);
	}

	public function testDeniedAuthenticationAlsoCounts(): void {
		/** @var IMemcacheWithTTL|MockObject $cache */
		$cache = $this->createMock(IMemcacheWithTTL::class);
		$cache->method('get')->willReturn(null);
		$cache->expects($this->once())->method('inc')->with('testhash_failures');

		$client = new TestableHordeImapClient();
		$client->outcomes = ['denied'];
		$client->enableRateLimiter($cache, 'testhash', $this->timeFactory);

		$this->expectException(Horde_Imap_Client_Exception::class);
		$this->expectExceptionMessage('Mail server denied authentication.');
		$client->doLogin();
	}

	public function testSetsTtlOnAuthFailure(): void {
		/** @var IMemcacheWithTTL|MockObject $cache */
		$cache = $this->createMock(IMemcacheWithTTL::class);
		$cache->method('get')->willReturn(null);
		$cache->expects($this->once())->method('inc')->with('testhash_failures');
		$cache->expects($this->once())->method('setTTL')->with('testhash_failures', 3 * 60 * 60);

		$client = new TestableHordeImapClient();
		$client->enableRateLimiter($cache, 'testhash', $this->timeFactory);

		$this->expectException(Horde_Imap_Client_Exception::class);
		$client->doLogin();
	}
}
