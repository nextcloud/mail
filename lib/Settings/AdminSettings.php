<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Settings;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\LDAP\ILDAPProvider;
use OCP\Settings\ISettings;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\SummaryTaskType;

class AdminSettings implements ISettings {
	/** @var IInitialStateService */
	private $initialStateService;

	/** @var ProvisioningManager */
	private $provisioningManager;

	/** @var AntiSpamService */
	private $antiSpamService;

	private GoogleIntegration $googleIntegration;
	private MicrosoftIntegration $microsoftIntegration;
	private IConfig $config;
	private AiIntegrationsService $aiIntegrationsService;
	private ClassificationSettingsService $classificationSettingsService;

	public function __construct(
		IInitialStateService $initialStateService,
		ProvisioningManager $provisioningManager,
		AntiSpamService $antiSpamService,
		GoogleIntegration $googleIntegration,
		MicrosoftIntegration $microsoftIntegration,
		IConfig $config,
		AiIntegrationsService $aiIntegrationsService,
		ClassificationSettingsService $classificationSettingsService,
		private Defaults $themingDefaults,
	) {
		$this->initialStateService = $initialStateService;
		$this->provisioningManager = $provisioningManager;
		$this->antiSpamService = $antiSpamService;
		$this->googleIntegration = $googleIntegration;
		$this->microsoftIntegration = $microsoftIntegration;
		$this->config = $config;
		$this->aiIntegrationsService = $aiIntegrationsService;
		$this->classificationSettingsService = $classificationSettingsService;
	}

	#[\Override]
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

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'layout_message_view',
			$this->config->getAppValue('mail', 'layout_message_view', 'threaded')
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'llm_processing',
			$this->aiIntegrationsService->isLlmProcessingEnabled(),
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'enabled_llm_free_prompt_backend',
			$this->aiIntegrationsService->isLlmAvailable(FreePromptTaskType::class)
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'enabled_llm_summary_backend',
			$this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class)
		);

		$this->initialStateService->provideLazyInitialState(
			Application::APP_ID,
			'ldap_aliases_integration',
			static function () {
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
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'microsoft_oauth_tenant_id',
			$this->microsoftIntegration->getTenantId(),
		);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'microsoft_oauth_client_id',
			$this->microsoftIntegration->getClientId(),
		);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'microsoft_oauth_redirect_url',
			$this->microsoftIntegration->getRedirectUrl(),
		);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'microsoft_oauth_docs',
			$this->themingDefaults->buildDocLinkToKey('admin-groupware-oauth-microsoft'),
		);

		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'importance_classification_default',
			$this->classificationSettingsService->isClassificationEnabledByDefault(),
		);

		return new TemplateResponse(Application::APP_ID, 'settings-admin');
	}

	#[\Override]
	public function getSection() {
		return 'groupware';
	}

	#[\Override]
	public function getPriority() {
		return 90;
	}
}
