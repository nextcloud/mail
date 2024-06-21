<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Avatar;

class AvatarFactory {
	/**
	 * Create a new avatar whose URL points to an internal endpoint
	 *
	 * @param string $url
	 * @return Avatar
	 */
	public function createInternal(string $url): Avatar {
		return new Avatar($url, null, false);
	}

	/**
	 * Create a new avatar whose URL points to an external endpoint
	 *
	 * @param string $url
	 * @param string $mime
	 * @return Avatar
	 */
	public function createExternal(string $url, string $mime): Avatar {
		return new Avatar($url, $mime);
	}
}
