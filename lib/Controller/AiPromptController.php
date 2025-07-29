<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsPromptsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;

class AiPromptController extends Controller {

	public function __construct(
		IRequest $request,
		private AiIntegrationsPromptsService $service,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @return JsonResponse
	 */
	#[TrapError]
	public function getPrompts(): JsonResponse {
		$prompts['event_data_prompt_preamble'] = $this->service->getEventDataPromptPreamble();
		$prompts['summarize_email_prompt'] = $this->service->getSummarizeEmailPrompt();
		$prompts['smart_reply_prompt_preamble'] = $this->service->getSmartReplyPromptPreamble();
		$prompts['smart_reply_prompt_postamble'] = $this->service->getSmartReplyPromptPostamble();
		$prompts['requires_followup_prompt_preamble'] = $this->service->getRequiresFollowupPromptPreamble();
		$prompts['requires_followup_prompt_postamble'] = $this->service->getRequiresFollowupPromptPostamble();

		return JsonResponse::success($prompts, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @param string $type
	 * @return JsonResponse
	 */
	#[TrapError]
	public function setPrompt(string $key, string $value): JsonResponse {
		switch ($key) {
			case 'event_data_prompt_preamble':
				$this->service->setEventDataPromptPreamble($value);
				break;
			case 'summarize_email_prompt':
				$this->service->setSummarizeEmailPrompt($value);
				break;
			case 'smart_reply_prompt_preamble':
				$this->service->setSmartReplyPreamble($value);
				break;
			case 'smart_reply_prompt_postamble':
				$this->service->setSmartReplyPostamble($value);
				break;
			case 'requires_followup_prompt_preamble':
				$this->service->setRequiresFollowupPreamble($value);
				break;
			case 'requires_followup_prompt_postamble':
				$this->service->setRequiresFollowupPostamble($value);
				break;
			default:
				return JsonResponse::error('Invalid prompt key', Http::STATUS_BAD_REQUEST);
		}

		return JsonResponse::success(null);
	}
	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function list(): JsonResponse {
		$list = $this->trustedSenderService->getTrusted(
			$this->uid
		);

		return JsonResponse::success($list);
	}
}
