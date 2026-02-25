<?php


namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\TrustedSender;
use OCA\Mail\Service\InternalAddressService;
use OCA\Mail\Service\TrustedSenderService;
use OCA\Mail\UserMigration\Service\AppConfigurationMigrationService;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCA\UserMigration\ExportDestination;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class AppConfigurationMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private IConfig $config;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->config = $this->createMock(IConfig::class);
	}

	public function testExportsMultipleAppConfigurations(): void {
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(AppConfigurationMigrationService::APP_CONFIGURATION_FILE, json_encode($this->getAppConfig()));

		$this->config->expects(self::once())->method('getUserKeys')->with(self::USER_ID, Application::APP_ID)->willReturn($this->getAppKeys());
		$this->config->method('getUserValue')->with(self::USER_ID, Application::APP_ID, self::callback(function ($appConfigKey): bool {
			$allowedKeys = $this->getAppKeys();
			return array_find($allowedKeys, function (mixed $value) use ($appConfigKey): bool {
				return $appConfigKey === $value;
			});
		}))->willReturnCallback(function ($userId, $appId, $appConfigKey): string {
			return $this->getAppValue($appConfigKey);
		});

		$service = new AppConfigurationMigrationService($this->config, $this->l);
		$service->exportAppConfiguration($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoAppConfiguration(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(AppConfigurationMigrationService::APP_CONFIGURATION_FILE, json_encode($trustedSendersList));

		$this->config->expects(self::once())->method('getUserKeys')->with(self::USER_ID, Application::APP_ID)->willReturn($trustedSendersList);
		$this->config->expects(self::never())->method('getUserValue');

		$service = new AppConfigurationMigrationService($this->config, $this->l);
		$service->exportAppConfiguration($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleAppConfigurations(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(AppConfigurationMigrationService::APP_CONFIGURATION_FILE)->willReturn(json_encode($this->getAppConfig()));

		$this->config->expects(self::exactly(3))->method('setUserValue')->with(self::USER_ID, Application::APP_ID, self::callback(function ($key) {
			return array_search($key, $this->getAppKeys()) !== false;
		}), self::callback(function ($searchedValue): bool {
			return array_find($this->getAppValues(), function (mixed $value) use ($searchedValue): bool {
				return $searchedValue === $value;
			});
		}));

		$service = new AppConfigurationMigrationService($this->config, $this->l);
		$service->importAppConfiguration($this->user, $this->importSource, $this->output);
	}

	public function testImportNoAppConfiguration(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(AppConfigurationMigrationService::APP_CONFIGURATION_FILE)->willReturn(json_encode($trustedSendersList));
		$this->config->expects(self::never())->method('setUserValue');
		$service = new AppConfigurationMigrationService($this->config, $this->l);
		$service->importAppConfiguration($this->user, $this->importSource, $this->output);
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

	private function getAppValue(string $key): null|string {
		$returnValue =  array_find_key($this->getAppConfig(), function ($value) use ($key) {
			return $key === $value['key'];
		});

		return $this->getAppConfig()[$returnValue]['value'];
	}

	private function getAppValues(): array {
		return array_map(function (array $appConfig) {
			return $appConfig['value'];
		}, $this->getAppConfig());
	}
}
