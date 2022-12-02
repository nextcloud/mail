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
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\LDAP\ILDAPProvider;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	/** @var IInitialStateService */
	private $initialStateService;

	/** @var ProvisioningManager */
	private $provisioningManager;

	/** @var AntiSpamService */
	private $antiSpamService;

	private GoogleIntegration $googleIntegration;
	private IConfig $config;

	public function __construct(IInitialStateService $initialStateService,
								ProvisioningManager $provisioningManager,
								AntiSpamService $antiSpamService,
								GoogleIntegration $googleIntegration,
								IConfig $config) {
		$this->initialStateService = $initialStateService;
		$this->provisioningManager = $provisioningManager;
		$this->antiSpamService = $antiSpamService;
		$this->googleIntegration = $googleIntegration;
		$this->config = $config;
	}

	public function getForm() {
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'provisioning_settings',
			$this->provisioningManager->getConfigs()
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'antispam_setting',
			[
				'spam' => $this->antiSpamService->getSpamEmail(),
				'ham' => $this->antiSpamService->getHamEmail(),
			]
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'allow_new_mail_accounts',
			$this->config->getAppValue('mail', 'allow_new_mail_accounts', 'yes') === 'yes'
		);
		$this->initialStateService->provideLazyInitialState(
			Application::APP_ID,
			'ldap_aliases_integration',
			function () {
				return method_exists(ILDAPProvider::class, 'getMultiValueUserAttribute');
			}
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'google_oauth_client_id',
			$this->googleIntegration->getClientId(),
		);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'google_oauth_redirect_url',
			$this->googleIntegration->getRedirectUrl(),
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
