<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\ConfigLexicon;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\Preset;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

class ConfigLexiconTest extends TestCase {

	private ConfigLexicon $lexicon;

	protected function setUp(): void {
		parent::setUp();

		$this->lexicon = new ConfigLexicon();
	}

	public function testGetStrictness(): void {
		self::assertSame(Strictness::IGNORE, $this->lexicon->getStrictness());
	}

	public function testGetUserConfigsIsEmpty(): void {
		self::assertSame([], $this->lexicon->getUserConfigs());
	}

	/**
	 * @return array<string, array{ValueType, string|null}>
	 */
	public function appConfigProvider(): array {
		return [
			ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS => [ValueType::BOOL, '1'],
			ConfigLexicon::LLM_PROCESSING => [ValueType::BOOL, '0'],
			ConfigLexicon::LAYOUT_MESSAGE_VIEW => [ValueType::STRING, 'threaded'],
			ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT => [ValueType::BOOL, '1'],
			ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT => [ValueType::BOOL, '0'],
			ConfigLexicon::GOOGLE_OAUTH_CLIENT_ID => [ValueType::STRING, null],
			ConfigLexicon::GOOGLE_OAUTH_CLIENT_SECRET => [ValueType::STRING, null],
			ConfigLexicon::MICROSOFT_OAUTH_CLIENT_ID => [ValueType::STRING, null],
			ConfigLexicon::MICROSOFT_OAUTH_CLIENT_SECRET => [ValueType::STRING, null],
			ConfigLexicon::MICROSOFT_OAUTH_TENANT_ID => [ValueType::STRING, 'common'],
			ConfigLexicon::ANTISPAM_REPORTING_SPAM => [ValueType::STRING, null],
			ConfigLexicon::ANTISPAM_REPORTING_HAM => [ValueType::STRING, null],
		];
	}

	public function testAppConfigKeysMatchProvider(): void {
		$declaredKeys = array_map(
			static fn (Entry $entry): string => $entry->getKey(),
			$this->lexicon->getAppConfigs(),
		);

		sort($declaredKeys);
		$expectedKeys = array_keys($this->appConfigProvider());
		sort($expectedKeys);

		self::assertSame($expectedKeys, $declaredKeys);
	}

	public function testAppConfigTypesAndDefaults(): void {
		$expected = $this->appConfigProvider();

		foreach ($this->lexicon->getAppConfigs() as $entry) {
			$key = $entry->getKey();
			self::assertArrayHasKey($key, $expected, "Unexpected config key $key");
			[$type, $default] = $expected[$key];
			self::assertSame($type, $entry->getValueType(), "Wrong type for $key");
			self::assertSame($default, $entry->getDefault(Preset::NONE), "Wrong default for $key");
		}
	}
}
