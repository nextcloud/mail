<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use OCA\Mail\Service\ContactsIntegration;

/**
 * This class is just a think wrapper around the contacts integration to use it
 * as avatar source
 */
class AddressbookSource implements IAvatarSource {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	public function __construct(ContactsIntegration $contactsIntegration) {
		$this->contactsIntegration = $contactsIntegration;
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
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
	#[\Override]
	public function fetch(string $email, AvatarFactory $factory) {
		$url = $this->contactsIntegration->getPhoto($email);

		if ($url === false || $url === null) {
			return null;
		}

		return $factory->createInternal($url);
	}
}
