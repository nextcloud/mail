<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Settings;

use OCA\Assistant\AppInfo\Application;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\Settings\ISettings;
use OCP\TextProcessing\FreePromptTaskType;

class AiPromptSettings implements ISettings {

	public function __construct(
		private IAppConfig $appConfig,
		private IInitialState $initialStateService,
		private AiIntegrationsService $aiIntegrationsService,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {

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

		return new TemplateResponse(Application::APP_ID, 'ai-prompt-settings');
	}

	public function getSection(): string {
		if ($this->aiIntegrationsService->isLlmProcessingEnabled()) {
			return null;
		}
		return 'ai';
	}

	public function getPriority(): int {
		return 11;
	}
}
