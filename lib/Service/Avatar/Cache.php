<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Avatar;

use OCP\ICache;
use OCP\ICacheFactory;

class Cache {
	// Cache for one week
	public const CACHE_TTL = 7 * 24 * 60 * 60;

	/** @var ICache */
	private $cache;

	/** @var AvatarFactory */
	private $avatarFactory;

	public function __construct(ICacheFactory $cacheFactory, AvatarFactory $avatarFactory) {
		$this->cache = $cacheFactory->createDistributed('mail.avatars');
		$this->avatarFactory = $avatarFactory;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return string
	 */
	private function buildUrlKey(string $email, string $uid): string {
		return base64_encode(json_encode([$email, $uid]));
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @return string
	 */
	private function buildImageKey(string $url, string $uid): string {
		return base64_encode(json_encode([$url, $uid]));
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return Avatar|null|false the avatar if cached, false if cached but no value and null if not cached
	 */
	public function get(string $email, string $uid) {
		$cached = $this->cache->get($this->buildUrlKey($email, $uid));

		if (is_null($cached)) {
			return null;
		}
		if ($cached === false) {
			return false;
		}

		if ($cached['isExternal']) {
			return $this->avatarFactory->createExternal($cached['url'], $cached['mime']);
		}

		return $this->avatarFactory->createInternal($cached['url']);
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @param Avatar|null $avatar
	 *
	 * @return void
	 */
	public function add(string $email, string $uid, ?Avatar $avatar): void {
		$this->cache->set($this->buildUrlKey($email, $uid), $avatar === null ? false : $avatar->jsonSerialize(), self::CACHE_TTL);
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @return string|null cached image data
	 */
	public function getImage(string $url, string $uid) {
		return $this->cache->get($this->buildImageKey($url, $uid));
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @param string $image base64 encoded image data
	 *
	 * @return void
	 */
	public function addImage(string $url, string $uid, string $image): void {
		$this->cache->set($this->buildImageKey($url, $uid), $image, self::CACHE_TTL);
	}
}
