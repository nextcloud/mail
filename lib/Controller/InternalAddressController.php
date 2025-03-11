<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\InternalAddressService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class InternalAddressController extends Controller {
	private ?string $uid;

	public function __construct(
		IRequest $request,
		?string $userId,
		private InternalAddressService $internalAddressService,
	) {
		parent::__construct(Application::APP_ID, $request);

		$this->internalAddressService = $internalAddressService;
		$this->uid = $userId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $address
	 * @param string $type
	 * @return JsonResponse
	 */
	#[TrapError]
	public function setAddress(string $address, string $type): JsonResponse {
		$address = $this->internalAddressService->add(
			$this->uid,
			$address,
			$type
		)->jsonSerialize();

		return JsonResponse::success($address, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $address
	 * @param string $type
	 * @return JsonResponse
	 */
	#[TrapError]
	public function removeAddress(string $address, string $type): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$this->internalAddressService->add(
			$this->uid,
			$address,
			$type,
			false
		);

		return JsonResponse::success();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function list(): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$list = $this->internalAddressService->getInternalAddresses(
			$this->uid
		);

		return JsonResponse::success($list);
	}
}
