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
	 * @return Avatar|null
	 */
	public function getAvatar(string $email, string $uid): ?Avatar;

	/**
	 * @param string $email
	 * @param string $uid
	 * @return array|null image data
	 */
	public function getAvatarImage(string $email, string $uid);

	public function getCachedAvatar(string $email, string $uid): Avatar|false|null;
}
