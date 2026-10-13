<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Avatar;

interface IAvatarSource {
	/**
	 * Does this source query external services?
	 *
	 * @return bool
	 */
	public function isExternal():bool ;

	/**
	 * @param string $userId uid of the user the lookup is performed for
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
	public function fetch(string $userId, string $email, AvatarFactory $factory);
}
