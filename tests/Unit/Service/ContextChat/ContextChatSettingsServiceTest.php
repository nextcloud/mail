<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\ContextChat;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\ContextChat\ContextChatSettingsService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ContextChatSettingsServiceTest extends TestCase {

	private IUserPreferences&MockObject $preferences;
	private IAppConfig&MockObject $appConfig;
	private ContextChatSettingsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->preferences = $this->createMock(IUserPreferences::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->service = new ContextChatSettingsService(
			$this->preferences,
			$this->appConfig,
		);
	}

	public function testIsIndexingEnabledByDefault(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT, false)
			->willReturn(true);

		self::assertTrue($this->service->isIndexingEnabledByDefault());
	}

	public function testSetIndexingEnabledByDefault(): void {
		$this->appConfig->expects(self::once())
			->method('setValueBool')
			->with(Application::APP_ID, ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT, true);

		$this->service->setIndexingEnabledByDefault(true);
	}

	public function testIsIndexingEnabledFallsBackToDefault(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT, false)
			->willReturn(true);
		$this->preferences->expects(self::once())
			->method('getPreference')
			->with('user123', 'index-context-chat', 'true')
			->willReturn('true');

		self::assertTrue($this->service->isIndexingEnabled('user123'));
	}

	public function testIsIndexingEnabledRespectsUserOptOut(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT, false)
			->willReturn(false);
		$this->preferences->expects(self::once())
			->method('getPreference')
			->with('user123', 'index-context-chat', 'false')
			->willReturn('false');

		self::assertFalse($this->service->isIndexingEnabled('user123'));
	}
}
