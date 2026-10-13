<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\ConfigLexicon;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Heal appconfig keys that switched to typed BOOL storage in the config lexicon
 * but may still hold a legacy value of a different type in the database, e.g. a
 * string 'yes'/'no' written by `occ config:app:set` before the lexicon existed
 * (its --type defaults to string). Reading such a value via getValueBool()
 * raises an AppConfigTypeConflictException and takes down the whole app.
 *
 * @psalm-api
 */
class Version5120Date20260820000000 extends SimpleMigrationStep {
	private const BOOL_KEYS = [
		ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS,
		ConfigLexicon::LLM_PROCESSING,
		ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT,
		ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT,
	];

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$values = $this->appConfig->getAllAppValues();
		foreach (self::BOOL_KEYS as $key) {
			if (!array_key_exists($key, $values)) {
				continue;
			}

			$enabled = (bool)$values[$key];
			$this->appConfig->deleteAppValue($key);
			$this->appConfig->setAppValueBool($key, $enabled);
		}
	}
}
