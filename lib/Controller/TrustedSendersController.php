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
	public function __construct(
		IRequest $request,
		private ?string $userId,
		private ITrustedSenderService $trustedSenderService,
	) {
		parent::__construct(Application::APP_ID, $request);
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
		if ($this->userId === null) {
			return JsonResponse::fail([], Http::STATUS_UNAUTHORIZED);
		}

		$this->trustedSenderService->trust(
			$this->userId,
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
		if ($this->userId === null) {
			return JsonResponse::fail([], Http::STATUS_UNAUTHORIZED);
		}

		$this->trustedSenderService->trust(
			$this->userId,
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
		if ($this->userId === null) {
			return JsonResponse::fail([], Http::STATUS_UNAUTHORIZED);
		}

		$list = $this->trustedSenderService->getTrusted(
			$this->userId
		);

		return JsonResponse::success($list);
	}
}
