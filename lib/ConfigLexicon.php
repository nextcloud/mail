<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config lexicon for the Mail app.
 *
 * Central declaration of all app config keys: their type, default value and a
 * short definition surfaced by `occ config:app:*`. Please keep this file in
 * sync whenever a config key is added, changed or removed.
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const ALLOW_NEW_MAIL_ACCOUNTS = 'allow_new_mail_accounts';
	public const LLM_PROCESSING = 'llm_processing';
	public const LAYOUT_MESSAGE_VIEW = 'layout_message_view';
	public const IMPORTANCE_CLASSIFICATION_DEFAULT = 'importance_classification_default';
	public const INDEX_CONTEXT_CHAT_DEFAULT = 'index_context_chat_default';
	public const GOOGLE_OAUTH_CLIENT_ID = 'google_oauth_client_id';
	public const GOOGLE_OAUTH_CLIENT_SECRET = 'google_oauth_client_secret';
	public const MICROSOFT_OAUTH_CLIENT_ID = 'microsoft_oauth_client_id';
	public const MICROSOFT_OAUTH_CLIENT_SECRET = 'microsoft_oauth_client_secret';
	public const MICROSOFT_OAUTH_TENANT_ID = 'microsoft_oauth_tenant_id';
	public const ANTISPAM_REPORTING_SPAM = 'antispam_reporting_spam';
	public const ANTISPAM_REPORTING_HAM = 'antispam_reporting_ham';

	#[\Override]
	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	#[\Override]
	public function getAppConfigs(): array {
		return [
			new Entry(
				self::ALLOW_NEW_MAIL_ACCOUNTS,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether users are allowed to set up new mail accounts themselves',
			),
			new Entry(
				self::LLM_PROCESSING,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether large language model processing (e.g. thread summaries) is enabled',
			),
			new Entry(
				self::LAYOUT_MESSAGE_VIEW,
				ValueType::STRING,
				defaultRaw: 'threaded',
				definition: 'Default message list layout for users that did not choose one themselves',
			),
			new Entry(
				self::IMPORTANCE_CLASSIFICATION_DEFAULT,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether importance classification is enabled by default for users that did not toggle the preference',
			),
			new Entry(
				self::INDEX_CONTEXT_CHAT_DEFAULT,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether mails are indexed for Context Chat by default for users that did not toggle the preference',
			),
			new Entry(
				self::GOOGLE_OAUTH_CLIENT_ID,
				ValueType::STRING,
				definition: 'OAuth client ID used to connect Google (Gmail) accounts',
			),
			new Entry(
				self::GOOGLE_OAUTH_CLIENT_SECRET,
				ValueType::STRING,
				definition: 'OAuth client secret used to connect Google (Gmail) accounts',
				note: 'Stored encrypted by the app before being written',
			),
			new Entry(
				self::MICROSOFT_OAUTH_CLIENT_ID,
				ValueType::STRING,
				definition: 'OAuth client ID used to connect Microsoft (Outlook) accounts',
			),
			new Entry(
				self::MICROSOFT_OAUTH_CLIENT_SECRET,
				ValueType::STRING,
				definition: 'OAuth client secret used to connect Microsoft (Outlook) accounts',
				note: 'Stored encrypted by the app before being written',
			),
			new Entry(
				self::MICROSOFT_OAUTH_TENANT_ID,
				ValueType::STRING,
				defaultRaw: 'common',
				definition: 'Microsoft OAuth tenant ID used to connect Microsoft (Outlook) accounts',
			),
			new Entry(
				self::ANTISPAM_REPORTING_SPAM,
				ValueType::STRING,
				definition: 'Email address spam reports are forwarded to',
			),
			new Entry(
				self::ANTISPAM_REPORTING_HAM,
				ValueType::STRING,
				definition: 'Email address ham (not spam) reports are forwarded to',
			),
		];
	}

	#[\Override]
	public function getUserConfigs(): array {
		return [];
	}
}
