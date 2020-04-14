<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Service\Provisioning;

use OCA\Mail\AppInfo\Application;
use OCP\IConfig;

class ConfigMapper {
	private const CONFIG_KEY = 'provisioning_settings';

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function load(): ?Config {
		$raw = $this->config->getAppValue(
			Application::APP_ID,
			self::CONFIG_KEY
		);
		if ($raw === '') {
			// Not config set yet
			return null;
		}
		return new Config(json_decode($raw, true));
	}

	public function save(Config $config): Config {
		$this->config->setAppValue(
			Application::APP_ID,
			self::CONFIG_KEY,
			json_encode($config)
		);

		return $config;
	}
}
