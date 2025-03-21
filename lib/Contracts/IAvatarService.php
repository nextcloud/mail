<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Service\Avatar\Avatar;

interface IAvatarService {
	/**
	 * Try to find an avatar for the given email address
	 *
	 * @param string $email
	 * @param string $uid
	 * @param bool $cachedOnly
	 * @return Avatar|null|false the avatar if found, false if $cachedOnly is true and no value cached and null if not found
	 */
	public function getAvatar(string $email, string $uid, bool $cachedOnly = false): mixed;

	/**
	 * @param string $email
	 * @param string $uid
	 * @return array|null image data
	 */
	public function getAvatarImage(string $email, string $uid);
}
