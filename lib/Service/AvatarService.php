<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\Cache as AvatarCache;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\Downloader;

class AvatarService implements IAvatarService {
	/** @var AvatarCache */
	private $cache;

	/** @var Downloader */
	private $downloader;

	/** @var CompositeAvatarSource */
	private $source;

	/** @var AvatarFactory */
	private $avatarFactory;

	/** @var IUserPreferences */
	private $preferences;

	/**
	 * @param CompositeAvatarSource $source
	 * @param Downloader $downloader
	 * @param AvatarCache $cache
	 * @param AvatarFactory $avatarFactory
	 * @param IUserPreferences $preferences
	 */
	public function __construct(CompositeAvatarSource $source,
		Downloader $downloader,
		AvatarCache $cache,
		AvatarFactory $avatarFactory,
		IUserPreferences $preferences) {
		$this->source = $source;
		$this->cache = $cache;
		$this->downloader = $downloader;
		$this->avatarFactory = $avatarFactory;
		$this->preferences = $preferences;
	}

	/**
	 * @return bool
	 */
	private function externalAvatarsAllowed(string $uid): bool {
		return $this->preferences->getPreference($uid, 'external-avatars', 'true') === 'true';
	}

	/**
	 * @param Avatar $avatar
	 */
	private function hasAllowedMime(Avatar $avatar): bool {
		if ($avatar->isExternal()) {
			$mime = $avatar->getMime();

			return in_array($mime,
				[
					'image/jpeg',
					'image/png',
					'image/x-icon',
					'image/vnd.microsoft.icon',
				]);
		} else {
			// We trust internal URLs by default
			return true;
		}
	}

	public function getCachedAvatar(string $email, string $uid): Avatar|false|null {
		return $this->cache->get($email, $uid);
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return Avatar|null
	 */
	#[\Override]
	public function getAvatar(string $email, string $uid): ?Avatar {
		$cachedAvatar = $this->cache->get($email, $uid);
		if ($cachedAvatar) {
			return $cachedAvatar;
		}
		if ($cachedAvatar === false) {
			// We know there is no avatar
			return null;
		}

		$avatar = $this->source->fetch($email, $this->avatarFactory, $this->externalAvatarsAllowed($uid));
		if (is_null($avatar) || !$this->hasAllowedMime($avatar)) {
			// Cannot locate any avatar -> nothing to do here

			$this->cache->add($email, $uid, null);

			return null;
		}

		// Cache for the next call
		$this->cache->add($email, $uid, $avatar);

		return $avatar;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return array|null image data
	 */
	#[\Override]
	public function getAvatarImage(string $email, string $uid) {
		$avatar = $this->getAvatar($email, $uid);
		if (is_null($avatar)) {
			return null;
		}

		$cachedImage = $this->cache->getImage($avatar->getUrl(), $uid);
		if (!is_null($cachedImage)) {
			return [$avatar, base64_decode($cachedImage)];
		}

		$image = $this->downloader->download($avatar->getUrl());
		if (is_null($image)) {
			return null;
		}

		// Cache for the next call
		$this->cache->addImage($avatar->getUrl(), $uid, base64_encode($image));

		return [$avatar, $image];
	}
}
