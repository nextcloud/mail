<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigrateProvisioningConfig implements IRepairStep {

	/** @var ProvisioningManager */
	private $provisioningManager;

	/** @var IConfig */
	private $config;

	public function __construct(ProvisioningManager $provisioningManager,
								IConfig $config) {
		$this->provisioningManager = $provisioningManager;
		$this->config = $config;
	}

	public function getName(): string {
		return 'Migrate Mail provisioning config from config.php to the database';
	}

	public function run(IOutput $output) {
		$fromConfigRaw = $this->config->getSystemValue('app.mail.accounts.default');
		if ($fromConfigRaw === '') {
			$output->info("No old config found");
			return;
		}

		if ($this->provisioningManager->getConfig() !== null) {
			$output->info("Mail provisioning config already set, ignoring old config");
			return;
		}

		$this->provisioningManager->importConfig($fromConfigRaw);
		$this->config->deleteSystemValue('app.mail.accounts.default');
		$output->info("Config migrated. Accounts not updated yet");
	}
}
