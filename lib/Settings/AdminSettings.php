<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Settings;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Service\Provisioning\Config;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IInitialStateService;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {

	/** @var IInitialStateService */
	private $initialStateService;

	/** @var ProvisioningManager */
	private $provisioningManager;

	public function __construct(IInitialStateService $initialStateService,
								ProvisioningManager $provisioningManager) {
		$this->initialStateService = $initialStateService;
		$this->provisioningManager = $provisioningManager;
	}

	public function getForm() {
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'provisioning_settings',
			$this->provisioningManager->getConfig() ?? new Config([
				'active' => false,
				'email' => '%USERID%@domain.com',
				'imapUser' => '%USERID%@domain.com',
				'imapHost' => 'imap.domain.com',
				'imapPort' => 993,
				'imapSslMode' => 'ssl',
				'smtpUser' => '%USERID%@domain.com',
				'smtpHost' => 'smtp.domain.com',
				'smtpPort' => 587,
				'smtpSslMode' => 'tls',
			])
		);

		return new TemplateResponse(Application::APP_ID, 'settings-admin');
	}

	public function getSection() {
		return 'groupware';
	}

	public function getPriority() {
		return 90;
	}
}
