<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\Classification;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IUserPreferences;
use OCP\IConfig;

class ClassificationSettingsService {
	public function __construct(
		private IUserPreferences $preferences,
		private IConfig $config,
	) {
	}

	/**
	 * Whether the classification by importance is enabled for a given user.
	 */
	public function isClassificationEnabled(string $userId): bool {
		$appConfig = $this->config->getAppValue(
			Application::APP_ID,
			'importance_classification_default',
			'yes',
		);
		$preference = $this->preferences->getPreference(
			$userId,
			'tag-classified-messages',
			$appConfig === 'yes' ? 'true' : 'false',
		);
		return $preference === 'true';
	}

	/**
	 * Whether to classify important mails by default for all users that did not yet toggle the
	 * preference themselves.
	 */
	public function isClassificationEnabledByDefault(): bool {
		return $this->config->getAppValue(
			Application::APP_ID,
			'importance_classification_default',
			'yes'
		) === 'yes';
	}

	/**
	 * Enable or disable the classification of important mails for all users that did not yet toggle
	 * the preference themselves.
	 */
	public function setClassificationEnabledByDefault(bool $enabledByDefault): void {
		$this->config->setAppValue(
			Application::APP_ID,
			'importance_classification_default',
			$enabledByDefault ? 'yes' : 'no',
		);
	}
}
