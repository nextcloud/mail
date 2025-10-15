<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

interface IUserPreferences {
	/**
	 * @param string $userId
	 * @param string $key
	 * @param mixed $value
	 * @return mixed new value
	 */
	public function setPreference(string $userId, $key, $value);

	/**
	 * @param string $userId
	 * @param string $key
	 * @param mixed|null $default
	 */
	public function getPreference(string $userId, $key, $default = null);
}
