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
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;

class AdminSettings implements ISettings {
	public function __construct(
		private IInitialState $initialStateService,
		private ProvisioningManager $provisioningManager,
		private AntiSpamService $antiSpamService,
		private GoogleIntegration $googleIntegration,
		private MicrosoftIntegration $microsoftIntegration,
		private IConfig $config,
		private AiIntegrationsService $aiIntegrationsService,
		private Defaults $themingDefaults,
		private ClassificationSettingsService $classificationSettingsService,
	) {
	}

	#[\Override]
	public function getForm() {
		$this->initialStateService->provideInitialState(
			'provisioning_settings',
			$this->provisioningManager->getConfigs()
		);

		$this->initialStateService->provideInitialState(
			'antispam_setting',
			[
				'spam' => $this->antiSpamService->getSpamEmail(),
				'ham' => $this->antiSpamService->getHamEmail(),
			]
		);

		$this->initialStateService->provideInitialState(
			'allow_new_mail_accounts',
			$this->config->getAppValue('mail', 'allow_new_mail_accounts', 'yes') === 'yes'
		);

		$this->initialStateService->provideInitialState(
			'layout_message_view',
			$this->config->getAppValue('mail', 'layout_message_view', 'threaded')
		);

		$this->initialStateService->provideInitialState(
			'llm_processing',
			$this->aiIntegrationsService->isLlmProcessingEnabled(),
		);

		$this->initialStateService->provideInitialState(
			'enabled_llm_free_prompt_backend',
			$this->aiIntegrationsService->isLlmAvailable(TextToText::ID)
		);

		$this->initialStateService->provideInitialState(
			'enabled_llm_summary_backend',
			$this->aiIntegrationsService->isLlmAvailable(TextToTextSummary::ID)
		);

		$this->initialStateService->provideInitialState(
			'google_oauth_client_id',
			$this->googleIntegration->getClientId(),
		);
		$this->initialStateService->provideInitialState(
			'google_oauth_redirect_url',
			$this->googleIntegration->getRedirectUrl(),
		);
		$this->initialStateService->provideInitialState(
			'importance_classification_default',
			$this->classificationSettingsService->isClassificationEnabledByDefault(),
		);
		$this->initialStateService->provideInitialState(
			'microsoft_oauth_tenant_id',
			$this->microsoftIntegration->getTenantId(),
		);
		$this->initialStateService->provideInitialState(
			'microsoft_oauth_client_id',
			$this->microsoftIntegration->getClientId(),
		);
		$this->initialStateService->provideInitialState(
			'microsoft_oauth_redirect_url',
			$this->microsoftIntegration->getRedirectUrl(),
		);
		$this->initialStateService->provideInitialState(
			'microsoft_oauth_docs',
			$this->themingDefaults->buildDocLinkToKey('admin-groupware-oauth-microsoft'),
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
