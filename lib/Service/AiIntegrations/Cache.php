<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\AiIntegrations;

use OCP\ICache;
use OCP\ICacheFactory;

class Cache {
	// Cache for one week
	public const CACHE_TTL = 7 * 24 * 60 * 60;
	// Failed or empty AI results are retried after one hour, doubling with every consecutive failure
	public const FAILURE_INITIAL_TTL = 60 * 60;
	public const FAILURE_MAX_TTL = 24 * 60 * 60;
	public const FAILURE_MARKER = '';

	/** @var ICache */
	private $cache;

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createLocal('mail.ai');
	}

	/**
	 * @param array $ids
	 * @return string
	 */
	public function buildUrlKey(array $ids): string {
		return base64_encode(json_encode($ids, JSON_THROW_ON_ERROR));
	}

	/**
	 * @param array $ids
	 *
	 * @return string|false the value if cached, false if cached but no value or not cached
	 */
	public function getValue(string $key) {
		$cached = $this->cache->get($key);

		if (is_null($cached) || $cached === false) {
			return false;
		}

		return $cached;
	}

	/**
	 * @param string $key
	 * @param string|null $value
	 *
	 * @return void
	 */
	public function addValue(string $key, ?string $value, int $ttl = self::CACHE_TTL): void {
		$this->cache->set($key, $value ?? false, $ttl);
	}

	/**
	 * Mark a key as failed and block retries until the backoff of the current
	 * attempt has elapsed.
	 */
	public function addFailure(string $key): void {
		$attempt = $this->getFailureCount($key) + 1;
		$ttl = self::getBackoffTtl($attempt);
		// Outlive the backoff so a retry right after it elapsed still counts as consecutive
		$this->cache->set($this->buildFailureCountKey($key), $attempt, $ttl * 2);
		$this->addValue($key, self::FAILURE_MARKER, $ttl);
	}

	/**
	 * Drop the recorded failures of a key so the next failure starts over at the
	 * initial backoff.
	 */
	public function resetFailures(string $key): void {
		$this->cache->remove($this->buildFailureCountKey($key));
	}

	public function getFailureCount(string $key): int {
		return (int)$this->cache->get($this->buildFailureCountKey($key));
	}

	public static function getBackoffTtl(int $attempt): int {
		if ($attempt < 1) {
			return self::FAILURE_INITIAL_TTL;
		}

		return (int)min(self::FAILURE_INITIAL_TTL * 2 ** ($attempt - 1), self::FAILURE_MAX_TTL);
	}

	private function buildFailureCountKey(string $key): string {
		return $key . ':failures';
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function remove(string $key): void {
		$this->cache->remove($key);
	}

}
