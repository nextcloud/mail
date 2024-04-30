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
