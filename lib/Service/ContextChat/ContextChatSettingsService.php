<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\ContextChat;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IUserPreferences;
use OCP\IConfig;

class ContextChatSettingsService {
	public function __construct(
		private IUserPreferences $preferences,
		private IConfig $config,
	) {
	}

	/**
	 * Whether the classification by importance is enabled for a given user.
	 */
	public function isIndexingEnabled(string $userId): bool {
		$appConfig = $this->config->getAppValue(
			Application::APP_ID,
			'index_context_chat_default',
			'no',
		);
		$preference = $this->preferences->getPreference(
			$userId,
			'index-context-chat',
			$appConfig !== 'no' ? 'true' : 'false',
		);
		return $preference === 'true';
	}

	/**
	 * Whether to index mails by default for all users that did not yet toggle the
	 * preference themselves.
	 */
	public function isIndexingEnabledByDefault(): bool {
		return $this->config->getAppValue(
			Application::APP_ID,
			'index_context_chat_default',
			'no'
		) !== 'no';
	}

	/**
	 * Enable or disable the indexing of mails for all users that did not yet toggle
	 * the preference themselves.
	 */
	public function setIndexingEnabledByDefault(bool $enabledByDefault): void {
		$this->config->setAppValue(
			Application::APP_ID,
			'index_context_chat_default',
			$enabledByDefault ? 'yes' : 'no',
		);
	}
}
