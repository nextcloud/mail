<?php

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
use OCA\Mail\Service\Avatar\Cache as AvatarCache;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\Downloader;
use OCA\Mail\Service\Avatar\IAvatarSource;
use OCP\IURLGenerator;

class AvatarService implements IAvatarService {

	/** @var AvatarCache */
	private $cache;

	/** @var Downloader */
	private $downloader;

	/** @var IAvatarSource */
	private $source;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(CompositeAvatarSource $source, Downloader $downloader, AvatarCache $cache, IURLGenerator $urlGenerator) {
		$this->source = $source;
		$this->cache = $cache;
		$this->urlGenerator = $urlGenerator;
		$this->downloader = $downloader;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return string|null
	 */
	public function getAvatarUrl($email, $uid) {
		$cachedUrl = $this->cache->getUrl($email, $uid);
		if (!is_null($cachedUrl)) {
			return $cachedUrl;
		}

		$url = $this->source->fetch($email, $uid);
		if (is_null($url)) {
			// Cannot locate any avatar -> nothing to do here
			return null;
		}

		// Cache for the next call
		$this->cache->addUrl($email, $uid, $url);

		return $url;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return mixed|null image data
	 */
	public function getAvatarImage($email, $uid) {
		$url = $this->cache->getUrl($email, $uid);
		if (is_null($url)) {
			return null;
		}

		$cachedImage = $this->cache->getImage($url, $uid);
		if (!is_null($cachedImage)) {
			return base64_decode($cachedImage);
		}

		$image = $this->downloader->download($url);
		if (is_null($image)) {
			return null;
		}

		// Cache for the next call
		$this->cache->addImage($url, $uid, base64_encode($image));

		return $image;
	}

}
