<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\UserMigration\Service\AppConfigMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class AppConfigMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private AppConfigMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(AppConfigMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);
	}

	public function testExportsMultipleAppConfigurations(): void {
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(AppConfigMigrationService::APP_CONFIGURATION_FILE, json_encode($this->getAppConfig()));

		$this->serviceMock->getParameter('config')->expects(self::once())->method('getUserKeys')->with(self::USER_ID, Application::APP_ID)->willReturn($this->getAppKeys());
		$this->serviceMock->getParameter('config')->method('getUserValue')->with(self::USER_ID, Application::APP_ID, self::callback(function ($appConfigKey): bool {
			return in_array($appConfigKey, $this->getAppKeys(), true);
		}))->willReturnCallback(function ($userId, $appId, $appConfigKey): string {
			return $this->getAppValue($appConfigKey);
		});

		$this->migrationService->exportAppConfiguration($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoAppConfiguration(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(AppConfigMigrationService::APP_CONFIGURATION_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('config')->expects(self::once())->method('getUserKeys')->with(self::USER_ID, Application::APP_ID)->willReturn($trustedSendersList);
		$this->serviceMock->getParameter('config')->expects(self::never())->method('getUserValue');

		$this->migrationService->exportAppConfiguration($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleAppConfigurations(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(AppConfigMigrationService::APP_CONFIGURATION_FILE)->willReturn(json_encode($this->getAppConfig()));

		$this->serviceMock->getParameter('config')->expects(self::exactly(3))->method('setUserValue')->with(self::USER_ID, Application::APP_ID, self::callback(function ($key) {
			return in_array($key, $this->getAppKeys());
		}), self::callback(function ($searchedValue): bool {
			return in_array($searchedValue, $this->getAppValues(), true);
		}));

		$this->migrationService->importAppConfiguration($this->user, $this->importSource, $this->output);
	}

	public function testImportNoAppConfiguration(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(AppConfigMigrationService::APP_CONFIGURATION_FILE)->willReturn(json_encode($trustedSendersList));

		$this->serviceMock->getParameter('config')->expects(self::never())->method('setUserValue');

		$this->migrationService->importAppConfiguration($this->user, $this->importSource, $this->output);
	}

	private function getAppConfig(): array {
		return [
			['key' => 'account-settings',
				'value' => '[{\"accountId\":19,\"collapsed\":false}]'],
			['key' => 'collect-data',
				'value' => 'true'
			],
			['key' => 'ui-heartbeat',
				'value' => '1770367800']
		];
	}

	private function getAppKeys(): array {
		return array_map(function (array $appConfig) {
			return $appConfig['key'];
		}, $this->getAppConfig());
	}

	private function getAppValue(string $key): ?string {
		foreach ($this->getAppConfig() as $appConfig) {
			if ($appConfig['key'] === $key) {
				return $appConfig['value'];
			}
		}
		return null;
	}

	private function getAppValues(): array {
		return array_map(function (array $appConfig) {
			return $appConfig['value'];
		}, $this->getAppConfig());
	}
}
