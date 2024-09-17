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
		return base64_encode(json_encode($ids));
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
	public function addValue(string $key, ?string $value): void {
		$this->cache->set($key, $value ?? false, self::CACHE_TTL);
	}


}
