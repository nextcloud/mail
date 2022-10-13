<?php

declare(strict_types=1);

/**
 * @author Jakob Sack <mail@jakobsack.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\Cache as AvatarCache;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\Downloader;
use OCP\IURLGenerator;

class AvatarService implements IAvatarService {
	/** @var AvatarCache */
	private $cache;

	/** @var Downloader */
	private $downloader;

	/** @var CompositeAvatarSource */
	private $source;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var AvatarFactory */
	private $avatarFactory;

	/** @var IUserPreferences */
	private $preferences;

	/**
	 * @param CompositeAvatarSource $source
	 * @param Downloader $downloader
	 * @param AvatarCache $cache
	 * @param IURLGenerator $urlGenerator
	 * @param AvatarFactory $avatarFactory
	 * @param IUserPreferences $preferences
	 */
	public function __construct(CompositeAvatarSource $source,
								Downloader $downloader,
								AvatarCache $cache,
								IURLGenerator $urlGenerator,
								AvatarFactory $avatarFactory,
								IUserPreferences $preferences) {
		$this->source = $source;
		$this->cache = $cache;
		$this->urlGenerator = $urlGenerator;
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
				]);
		} else {
			// We trust internal URLs by default
			return true;
		}
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return Avatar|null
	 */
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
