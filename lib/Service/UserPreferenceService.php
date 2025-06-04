<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IUserPreferences;
use OCP\IConfig;

class UserPreferenceService implements IUserPreferences {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @param string $userId
	 * @param string $key
	 * @param mixed $value
	 * @return mixed new value
	 */
	#[\Override]
	public function setPreference(string $userId, $key, $value) {
		$this->config->setUserValue($userId, 'mail', $key, $value);
		return $value;
	}

	/**
	 * @param string $userId
	 * @param string $key
	 * @param mixed|null $default
	 * @return string
	 */
	#[\Override]
	public function getPreference(string $userId, $key, $default = null) {
		return $this->config->getUserValue($userId, 'mail', $key, $default);
	}
}
