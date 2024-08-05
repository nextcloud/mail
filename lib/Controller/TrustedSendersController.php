<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class TrustedSendersController extends Controller {
	private ?string $uid;
	private ITrustedSenderService $trustedSenderService;

	public function __construct(IRequest $request,
		?string $UserId,
		ITrustedSenderService $trustedSenderService) {
		parent::__construct(Application::APP_ID, $request);

		$this->uid = $UserId;
		$this->trustedSenderService = $trustedSenderService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @param string $type
	 * @return JsonResponse
	 */
	#[TrapError]
	public function setTrusted(string $email, string $type): JsonResponse {
		$this->trustedSenderService->trust(
			$this->uid,
			$email,
			$type
		);

		return JsonResponse::success(null, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @param string $type
	 * @return JsonResponse
	 */
	#[TrapError]
	public function removeTrust(string $email, string $type): JsonResponse {
		$this->trustedSenderService->trust(
			$this->uid,
			$email,
			$type,
			false
		);

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
