<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\AiIntegrations;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\AiIntegrations\Cache;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;

class CacheTest extends TestCase {

	private ICacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $cacheImpl;
	private Cache $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheImpl = $this->createMock(ICache::class);
		$this->cacheFactory->expects($this->once())
			->method('createLocal')
			->with('mail.ai')
			->willReturn($this->cacheImpl);
		$this->cache = new Cache($this->cacheFactory);
	}

	public function testFirstFailureUsesInitialTtl(): void {
		$this->cacheImpl->method('get')
			->with('key:failures')
			->willReturn(null);
		$written = [];
		$this->cacheImpl->method('set')
			->willReturnCallback(function (string $key, $value, int $ttl) use (&$written) {
				$written[$key] = [$value, $ttl];
				return true;
			});

		$this->cache->addFailure('key');

		self::assertSame([Cache::FAILURE_MARKER, Cache::FAILURE_INITIAL_TTL], $written['key']);
		self::assertSame([1, Cache::FAILURE_INITIAL_TTL * 2], $written['key:failures']);
	}

	public function testConsecutiveFailureDoublesTtl(): void {
		$this->cacheImpl->method('get')
			->with('key:failures')
			->willReturn(3);
		$written = [];
		$this->cacheImpl->method('set')
			->willReturnCallback(function (string $key, $value, int $ttl) use (&$written) {
				$written[$key] = [$value, $ttl];
				return true;
			});

		$this->cache->addFailure('key');

		self::assertSame([Cache::FAILURE_MARKER, Cache::FAILURE_INITIAL_TTL * 8], $written['key']);
		self::assertSame([4, Cache::FAILURE_INITIAL_TTL * 16], $written['key:failures']);
	}

	public function testBackoffIsCapped(): void {
		$this->cacheImpl->method('get')
			->with('key:failures')
			->willReturn(42);
		$this->cacheImpl->expects(self::exactly(2))
			->method('set')
			->willReturnCallback(function (string $key, $value, int $ttl) {
				if ($key === 'key') {
					self::assertSame(Cache::FAILURE_MAX_TTL, $ttl);
				} else {
					self::assertSame(Cache::FAILURE_MAX_TTL * 2, $ttl);
				}
				return true;
			});

		$this->cache->addFailure('key');
	}

	public function testResetFailures(): void {
		$this->cacheImpl->expects(self::once())
			->method('remove')
			->with('key:failures');

		$this->cache->resetFailures('key');
	}

	public static function backoffTtlDataProvider(): array {
		return [
			[0, Cache::FAILURE_INITIAL_TTL],
			[1, Cache::FAILURE_INITIAL_TTL],
			[2, Cache::FAILURE_INITIAL_TTL * 2],
			[3, Cache::FAILURE_INITIAL_TTL * 4],
			[1000, Cache::FAILURE_MAX_TTL],
		];
	}

	/**
	 * @dataProvider backoffTtlDataProvider
	 */
	public function testGetBackoffTtl(int $attempt, int $expected): void {
		self::assertSame($expected, Cache::getBackoffTtl($attempt));
	}
}
