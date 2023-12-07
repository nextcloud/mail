<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	public function getPreference(string $userId, $key, $default = null) {
		return $this->config->getUserValue($userId, 'mail', $key, $default);
	}
}
