<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\QuickActionsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class ActionStepController extends Controller {
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
	public function findAllStepsForAction(int $actionId): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$actionSteps = $this->quickActionsService->findAllActionSteps($actionId, $this->uid);

		return JsonResponse::success($actionSteps);
	}

	/**
	 * @NoAdminRequired
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function create(string $name, int $order, int $actionId, ?int $tagId = null, ?int $mailboxId = null): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$action = $this->quickActionsService->find($actionId, $this->uid);
			if ($action === null) {
				return JsonResponse::fail('Action not found', Http::STATUS_BAD_REQUEST);
			}
			$accountId = $action->getAccountId();
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Account not found', Http::STATUS_BAD_REQUEST);
		}
		if ($account->getUserId() !== $this->uid) {
			return JsonResponse::fail('Account not found', Http::STATUS_BAD_REQUEST);
		}
		$actionStep = $this->quickActionsService->createActionStep($name, $order, $actionId, $tagId, $mailboxId);

		return JsonResponse::success($actionStep, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(int $id, string $name, int $order, ?int $tagId, ?int $mailboxId): JsonResponse {

		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$actionStep = $this->quickActionsService->findActionStep($id, $this->uid);

		$actionStep = $this->quickActionsService->updateActionStep($actionStep, $name, $order, $tagId, $mailboxId);

		return JsonResponse::success($actionStep, Http::STATUS_OK);
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
			$this->quickActionsService->deleteActionStep($id, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Action step not found', Http::STATUS_NOT_FOUND);
		}
	}
}
