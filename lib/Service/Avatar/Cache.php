<?php

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
	const CACHE_TTL = 7 * 24 * 60 * 60;

	/** @var ICache */
	private $cache;

	/**
	 * @param ICacheFactory $cacheFactory
	 */
	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->create('mail.avatars');
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return string
	 */
	private function buildUrlKey($email, $uid) {
		return base64_encode(json_encode([$email, $uid]));
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @return string
	 */
	private function buildImageKey($url, $uid) {
		return base64_encode(json_encode([$url, $uid]));
	}

	/**
	 * @param string $email
	 * @return string|null avatar URL
	 */
	public function getUrl($email, $uid) {
		return $this->cache->get($this->buildUrlKey($email, $uid));
	}

	/**
	 * @param string $email
	 * @param string $url
	 */
	public function addUrl($email, $uid, $url) {
		$this->cache->set($this->buildUrlKey($email, $uid), $url, self::CACHE_TTL);
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @return string|null cached image data
	 */
	public function getImage($url, $uid) {
		return $this->cache->get($this->buildImageKey($url, $uid));
	}

	/**
	 * @param string $url
	 * @param string $uid
	 * @param string $image base64 encoded image data
	 */
	public function addImage($url, $uid, $image) {
		$this->cache->set($this->buildImageKey($url, $uid), $image, self::CACHE_TTL);
	}

}
