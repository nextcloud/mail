<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\QuickActionsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class QuickActionsController extends Controller {
	private ?string $uid;

	public function __construct(
		IRequest $request,
		?string $userId,
		private QuickActionsService $quickActionsService,
		private AccountService $accountService,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->uid = $userId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function index(): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$actions = $this->quickActionsService->findAll($this->uid);

		return JsonResponse::success($actions);
	}

	/**
	 * @NoAdminRequired
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function create(string $name, int $accountId): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Account not found', Http::STATUS_BAD_REQUEST);
		}
		if ($account->getUserId() !== $this->uid) {
			return JsonResponse::fail('Account not found', Http::STATUS_BAD_REQUEST);
		}
		try {
			$quickAction = $this->quickActionsService->create($name, $accountId);
		} catch (ServiceException $e) {
			return JsonResponse::fail($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		return JsonResponse::success($quickAction, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(int $id, string $name): JsonResponse {

		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$quickAction = $this->quickActionsService->find($id, $this->uid);

		if ($quickAction === null) {
			return JsonResponse::error('Quick action not found', Http::STATUS_NOT_FOUND);
		}

		$quickAction = $this->quickActionsService->update($quickAction, $name);

		return JsonResponse::success($quickAction, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	public function destroy(int $id): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->quickActionsService->delete($id, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Quick action not found', Http::STATUS_NOT_FOUND);
		}
	}

}
