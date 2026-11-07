<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use OCA\Mail\Service\ContactsIntegration;
use OCP\IConfig;

/**
 * This class is just a think wrapper around the contacts integration to use it
 * as avatar source
 */
class AddressbookSource implements IAvatarSource {
	public function __construct(
		private ContactsIntegration $contactsIntegration,
		private IConfig $config,
	) {
	}

	/**
	 * Does this source query external services?
	 *
	 * @return bool
	 */
	#[\Override]
	public function isExternal(): bool {
		return false;
	}

	/**
	 * @param string $userId uid of the user the lookup is performed for
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
	#[\Override]
	public function fetch(string $userId, string $email, AvatarFactory $factory) {
		$url = $this->contactsIntegration->getPhoto($userId, $email);

		if ($url === false || $url === null) {
			return null;
		}

		// The Nextcloud system addressbook can expose automatically generated
		// initials as PHOTO. Let Gravatar and favicon take precedence over them.
		$path = parse_url($url, PHP_URL_PATH);
		if (is_string($path)
			&& preg_match('~/remote\.php/dav/addressbooks/system/system/system/([^/]+)\.vcf$~', $path, $matches) === 1) {
			$cardId = rawurldecode($matches[1]);
			$separator = strpos($cardId, ':');
			if ($separator !== false) {
				$contactUid = substr($cardId, $separator + 1);
				if ($this->config->getUserValue($contactUid, 'avatar', 'generated', 'false') === 'true') {
					return null;
				}
			}
		}

		return $factory->createInternal($url);
	}
}
