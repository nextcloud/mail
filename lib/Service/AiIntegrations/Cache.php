<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi<hamzamahjoubi22@proton.met>
 *
 * @author 2023 Hamza Mahjoubi <hamzamahjoubi22@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	private function buildUrlKey(array $ids): string {
		return base64_encode(json_encode($ids));
	}


	/**
	 * @param array $ids
	 *
	 * @return string|false the summary if cached, false if cached but no value or not cached
	 */
	public function getSummary(array $ids) {
		$cached = $this->cache->get($this->buildUrlKey($ids));

		if (is_null($cached) || $cached === false) {
			return false;
		}

		return $cached;
	}

	/**
	 * @param array $ids
	 * @param string|null $summary
	 *
	 * @return void
	 */
	public function addSummary(array $ids, ?string $summary): void {
		$this->cache->set($this->buildUrlKey($ids), $summary === null ? false : $summary, self::CACHE_TTL);
	}


}
