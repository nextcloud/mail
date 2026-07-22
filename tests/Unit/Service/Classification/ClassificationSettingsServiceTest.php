<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Classification;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ClassificationSettingsServiceTest extends TestCase {

	private IUserPreferences&MockObject $preferences;
	private IAppConfig&MockObject $appConfig;
	private ClassificationSettingsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->preferences = $this->createMock(IUserPreferences::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->service = new ClassificationSettingsService(
			$this->preferences,
			$this->appConfig,
		);
	}

	public function testIsClassificationEnabledByDefault(): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT, true)
			->willReturn(true);

		$result = $this->service->isClassificationEnabledByDefault();

		$this->assertTrue($result);
	}

	public function testSetClassificationEnabledByDefaultTrue(): void {
		$this->appConfig->expects(self::once())
			->method('setValueBool')
			->with(Application::APP_ID, ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT, true);

		$this->service->setClassificationEnabledByDefault(true);
	}
}
