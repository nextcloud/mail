<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Service\Provisioning\Config as ProvisioningConfig;
use OCA\Mail\Service\Provisioning\ConfigMapper as ProvisioningConfigMapper;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddSieveToProvisioningConfig implements IRepairStep {

	/** @var IConfig */
	private $config;

	/** @var ProvisioningConfigMapper */
	private $configMapper;

	public function __construct(IConfig $config, ProvisioningConfigMapper $configMapper) {
		$this->config = $config;
		$this->configMapper = $configMapper;
	}

	public function getName(): string {
		return 'Add sieve defaults to provisioning config';
	}

	public function run(IOutput $output) {
		if (!$this->shouldRun()) {
			return;
		}

		$config = $this->configMapper->load();
		if ($config === null) {
			return;
		}

		$reflectionClass = new \ReflectionClass(ProvisioningConfig::class);
		$reflectionProperty = $reflectionClass->getProperty('data');

		$reflectionProperty->setAccessible(true);
		$data = $reflectionProperty->getValue($config);

		if (!isset($data['sieveEnabled'])) {
			$data = array_merge($data, [
				'sieveEnabled' => false,
				'sieveHost' => '',
				'sievePort' => 4190,
				'sieveUser' => '',
				'sieveSslMode' => 'tls',
			]);
		}

		$reflectionProperty->setValue($config, $data);
		$this->configMapper->save($config);

		$output->info('added sieve defaults to provisioning config');
	}

	protected function shouldRun(): bool {
		$appVersion = $this->config->getAppValue('mail', 'installed_version', '0.0.0');
		return version_compare($appVersion, '1.9.0', '<');
	}
}
