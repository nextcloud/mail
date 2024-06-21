<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Avatar;

/**
 * Composition of all avatar sources for easier usage
 */
class CompositeAvatarSource {
	/** @var IAvatarSource[] */
	private $sources;

	public function __construct(AddressbookSource $addressbookSource,
		FaviconSource $faviconSource,
		GravatarSource $gravatarSource) {
		// This determines the priority of known sources
		$this->sources = [
			$addressbookSource,
			$gravatarSource,
			$faviconSource,
		];
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @param bool $queryExternal
	 * @return Avatar|null avatar URL if one can be found
	 */
	public function fetch(string $email, AvatarFactory $factory, bool $queryExternal) {
		foreach ($this->sources as $source) {
			if (!$queryExternal && $source->isExternal()) {
				// Skip this one
				continue;
			}

			$avatar = $source->fetch($email, $factory);

			if (is_null($avatar)) {
				continue;
			}

			return $avatar;
		}

		return null;
	}
}
