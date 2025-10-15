<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Classification;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ClassificationSettingsServiceTest extends TestCase {
	private ClassificationSettingsService $service;

	/** @var IUserPreferences|MockObject */
	private $preferences;

	/** @var IConfig|MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->preferences = $this->createMock(IUserPreferences::class);
		$this->config = $this->createMock(IConfig::class);

		$this->service = new ClassificationSettingsService(
			$this->preferences,
			$this->config,
		);
	}

	public static function isClassificationEnabledDataProvider(): array {
		return [
			['yes', 'true', true],
			['yes', 'false', false],
			['no', 'false', false],
			['no', 'true', true],
		];
	}

	/**
	 * @dataProvider isClassificationEnabledDataProvider
	 */
	public function testIsClassificationEnabled(
		string $appConfig,
		string $preference,
		bool $expected,
	): void {
		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'importance_classification_default', 'yes')
			->willReturn($appConfig);
		$this->preferences->expects(self::once())
			->method('getPreference')
			->with('user', 'tag-classified-messages', $appConfig === 'yes' ? 'true' : 'false')
			->willReturn($preference);

		$this->assertEquals($expected, $this->service->isClassificationEnabled('user'));
	}

	public static function isClassificationEnabledByDefaultDataProvider(): array {
		return [
			['yes', true],
			['no', false],
		];
	}

	/**
	 * @dataProvider isClassificationEnabledByDefaultDataProvider
	 */
	public function testIsImportanceClassificationEnabledByDefault(
		string $appConfig,
		bool $expected,
	): void {
		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'importance_classification_default', 'yes')
			->willReturn($appConfig);

		$this->assertEquals($expected, $this->service->isClassificationEnabledByDefault());
	}

	public static function setClassificationEnabledByDefaultDataProvider(): array {
		return [
			[true, 'yes'],
			[false, 'no'],
		];
	}

	/**
	 * @dataProvider setClassificationEnabledByDefaultDataProvider
	 */
	public function testSetClassificationEnabledByDefault(
		bool $enabledByDefault,
		string $expectedAppConfig,
	): void {
		$this->config->expects(self::once())
			->method('setAppValue')
			->with('mail', 'importance_classification_default', $expectedAppConfig);

		$this->service->setClassificationEnabledByDefault($enabledByDefault);
	}

}
