<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\ContextChat;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Contracts\IUserPreferences;
use OCP\IAppConfig;

class ContextChatSettingsService {
	public function __construct(
		private IUserPreferences $preferences,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * Whether the classification by importance is enabled for a given user.
	 */
	public function isIndexingEnabled(string $userId): bool {
		$enabledByDefault = $this->appConfig->getValueBool(
			Application::APP_ID,
			ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT,
			false,
		);
		$preference = $this->preferences->getPreference(
			$userId,
			'index-context-chat',
			$enabledByDefault ? 'true' : 'false',
		);
		return $preference === 'true';
	}

	/**
	 * Whether to index mails by default for all users that did not yet toggle the
	 * preference themselves.
	 */
	public function isIndexingEnabledByDefault(): bool {
		return $this->appConfig->getValueBool(
			Application::APP_ID,
			ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT,
			false,
		);
	}

	/**
	 * Enable or disable the indexing of mails for all users that did not yet toggle
	 * the preference themselves.
	 */
	public function setIndexingEnabledByDefault(bool $enabledByDefault): void {
		$this->appConfig->setValueBool(
			Application::APP_ID,
			ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT,
			$enabledByDefault,
		);
	}
}
