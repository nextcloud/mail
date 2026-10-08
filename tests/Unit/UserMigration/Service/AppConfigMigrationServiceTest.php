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
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AppConfigMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private const EXPORTED_ACCOUNT_ID = 19;
	private const IMPORTED_ACCOUNT_ID = 42;
	private const EXPORTED_MAILBOX_ID = 200;
	private const IMPORTED_MAILBOX_ID = 500;

	/** Byte length of the fixture below once encoded, pinned so the estimate cannot drift silently. */
	private const EXPECTED_APP_CONFIG_BYTES = 161;
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private AppConfigMigrationService $migrationService;
	private array $importedValues = [];

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

	public function testEstimatedExportSizeMatchesTheExportedPayload(): void {
		$this->serviceMock->getParameter('config')
			->method('getUserKeys')
			->with(self::USER_ID, Application::APP_ID)
			->willReturn($this->getAppKeys());
		$this->serviceMock->getParameter('config')
			->method('getUserValue')
			->willReturnCallback(fn (string $userId, string $appId, string $key): ?string => $this->getAppValue($key));

		$size = $this->migrationService->getEstimatedExportSize($this->user);

		self::assertSame(self::EXPECTED_APP_CONFIG_BYTES, $size);
		self::assertSame(strlen(json_encode($this->getAppConfig())), $size);
	}

	public function testExportsMultipleAppConfigurations(): void {
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(AppConfigMigrationService::APP_CONFIGURATION_FILE, json_encode($this->getAppConfig()));

		$this->serviceMock->getParameter('config')
			->expects(self::once())
			->method('getUserKeys')
			->with(self::USER_ID, Application::APP_ID)
			->willReturn($this->getAppKeys());

		$calls = [];
		$this->serviceMock->getParameter('config')
			->expects(self::exactly(3))
			->method('getUserValue')
			->willReturnCallback(function (string $userId,
				string $appId,
				string $key) use (&$calls): ?string {
				$calls[] = [$userId, $appId, $key];
				return $this->getAppValue($key);
			});

		$this->migrationService->exportAppConfiguration($this->user, $this->exportDestination, $this->output);

		$expected = array_map(fn (string $key) => [self::USER_ID, Application::APP_ID, $key], $this->getAppKeys());
		self::assertEqualsCanonicalizing($expected, $calls);
	}

	public function testExportsNoAppConfiguration(): void {
		$emptyAppConfig = [];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(AppConfigMigrationService::APP_CONFIGURATION_FILE, json_encode($emptyAppConfig));

		$this->serviceMock->getParameter('config')
			->expects(self::once())
			->method('getUserKeys')
			->with(self::USER_ID, Application::APP_ID)
			->willReturn($emptyAppConfig);
		$this->serviceMock->getParameter('config')
			->expects(self::never())
			->method('getUserValue');

		$this->migrationService->exportAppConfiguration($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleAppConfigurations(): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(AppConfigMigrationService::APP_CONFIGURATION_FILE)
			->willReturn(json_encode($this->getAppConfig()));

		$this->captureImportedValues(3);

		$this->migrationService->importAppConfiguration(
			$this->user,
			$this->importSource,
			$this->output,
			[self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID],
			[],
		);

		self::assertEqualsCanonicalizing(
			[
				AppConfigMigrationService::ACCOUNT_SETTINGS_KEY => json_encode([['accountId' => self::IMPORTED_ACCOUNT_ID, 'collapsed' => false]]),
				'collect-data' => 'true',
				'ui-heartbeat' => '1770367800',
			],
			$this->importedValues,
		);
	}

	public function testImportRemapsAccountSettingsAndStartMailboxId(): void {
		$appConfig = [
			['key' => AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'value' => json_encode([
					['accountId' => self::EXPORTED_ACCOUNT_ID, 'collapsed' => false],
					['accountId' => 999, 'collapsed' => true],
				])],
			['key' => AppConfigMigrationService::START_MAILBOX_ID_KEY,
				'value' => (string)self::EXPORTED_MAILBOX_ID],
		];
		$this->importSource->method('getFileContents')
			->willReturn(json_encode($appConfig));

		$this->captureImportedValues(2);

		$this->migrationService->importAppConfiguration(
			$this->user,
			$this->importSource,
			$this->output,
			[self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID],
			[self::EXPORTED_MAILBOX_ID => self::IMPORTED_MAILBOX_ID],
		);

		self::assertSame(
			[AppConfigMigrationService::ACCOUNT_SETTINGS_KEY => json_encode([['accountId' => self::IMPORTED_ACCOUNT_ID, 'collapsed' => false]]),
				AppConfigMigrationService::START_MAILBOX_ID_KEY => (string)self::IMPORTED_MAILBOX_ID],
			$this->importedValues,
		);
	}

	public static function provideUnmappableReferences(): array {
		return [
			'account was not imported' => [
				AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'[{"accountId":999,"collapsed":false}]',
			],
			'account settings are not a list' => [
				AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'{"accountId":19}',
			],
			'account settings are invalid JSON' => [
				AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'not json at all',
			],
			'account settings entry without account id' => [
				AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'[{"collapsed":false}]',
			],
			'start mailbox was not imported' => [
				AppConfigMigrationService::START_MAILBOX_ID_KEY,
				'999',
			],
			'start mailbox is not numeric' => [
				AppConfigMigrationService::START_MAILBOX_ID_KEY,
				'',
			],
		];
	}

	/**
	 * @dataProvider provideUnmappableReferences
	 */
	public function testImportSkipsUnmappableReferences(string $key, string $value): void {
		$this->importSource->method('getFileContents')
			->willReturn(json_encode([['key' => $key, 'value' => $value]]));

		$this->serviceMock->getParameter('config')
			->expects(self::never())
			->method('setUserValue');
		$this->serviceMock->getParameter('config')
			->expects(self::once())
			->method('deleteUserValue')
			->with(self::USER_ID, Application::APP_ID, $key);

		$this->migrationService->importAppConfiguration(
			$this->user,
			$this->importSource,
			$this->output,
			[self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID],
			[self::EXPORTED_MAILBOX_ID => self::IMPORTED_MAILBOX_ID],
		);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(AppConfigMigrationService::APP_CONFIGURATION_FILE)
			->willThrowException(new UserMigrationException());

		$this->serviceMock->getParameter('config')
			->expects(self::never())
			->method('setUserValue');

		$this->migrationService->importAppConfiguration($this->user, $this->importSource, $this->output, [], []);
	}

	public static function provideFileContentsWithNoSettingsImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'JSON object instead of list' => [json_encode(['unexpected' => 'object'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoSettingsImported
	 */
	public function testImportEmptyOrInvalidAppConfigurations(string $fileContents): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(AppConfigMigrationService::APP_CONFIGURATION_FILE)
			->willReturn($fileContents);

		$this->serviceMock->getParameter('config')
			->expects(self::never())
			->method('setUserValue');

		$this->migrationService->importAppConfiguration($this->user, $this->importSource, $this->output, [], []);
	}

	private function captureImportedValues(int $expectedCalls): void {
		$this->serviceMock->getParameter('config')
			->expects(self::exactly($expectedCalls))
			->method('setUserValue')
			->willReturnCallback(function (string $userId,
				string $appId,
				string $key,
				string $value): void {
				self::assertSame(self::USER_ID, $userId);
				self::assertSame(Application::APP_ID, $appId);
				$this->importedValues[$key] = $value;
			});
	}

	private function getAppConfig(): array {
		return [
			['key' => AppConfigMigrationService::ACCOUNT_SETTINGS_KEY,
				'value' => json_encode([['accountId' => self::EXPORTED_ACCOUNT_ID, 'collapsed' => false]])],
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
}
