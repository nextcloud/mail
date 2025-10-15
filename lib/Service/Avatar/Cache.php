<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$this->cache = $cacheFactory->createLocal('mail.avatars');
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
