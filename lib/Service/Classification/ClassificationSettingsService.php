<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IUserPreferences;
use OCP\IAppConfig;

class ClassificationSettingsService {
	public function __construct(
		private IUserPreferences $preferences,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * Whether to classify important mails by default for all users that did not yet toggle the
	 * preference themselves.
	 */
	public function isClassificationEnabledByDefault(): bool {
		return $this->appConfig->getValueString(
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
		$this->appConfig->setValueString(
			Application::APP_ID,
			'importance_classification_default',
			$enabledByDefault ? 'yes' : 'no',
		);
	}
}
