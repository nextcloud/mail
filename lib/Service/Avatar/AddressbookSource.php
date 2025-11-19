<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

/**
 * This class is just a think wrapper around the contacts integration to use it
 * as avatar source
 */
class AddressbookSource implements IAvatarSource {
	public function __construct(
		private readonly \OCA\Mail\Service\ContactsIntegration $contactsIntegration,
	) {
	}

	/**
	 * Does this source query external services?
	 */
	#[\Override]
	public function isExternal(): bool {
		return false;
	}

	/**
	 * @param string $email sender email address
	 * @return Avatar|null avatar URL if one can be found
	 */
	#[\Override]
	public function fetch(string $email, AvatarFactory $factory): ?\OCA\Mail\Service\Avatar\Avatar {
		$url = $this->contactsIntegration->getPhoto($email);

		if ($url === null) {
			return null;
		}

		return $factory->createInternal($url);
	}
}
