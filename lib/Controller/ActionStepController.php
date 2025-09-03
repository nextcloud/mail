
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
use OCA\Mail\Service\ActionStepService;
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
		private ActionStepService $actionStepService,
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
		$actionSteps = $this->actionStepService->findAll($actionId);

		return JsonResponse::success($actionSteps);
	}

	/**
	 * @NoAdminRequired
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function create(string $name, int $order, int $actionId, string $parameter): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$actionStep = $this->actionStepService->create($name, $order, $actionId, $parameter);

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
	public function update(int $id, string $name, string $parameter): JsonResponse {

		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$actionStep = $this->actionStepService->find($id, $this->uid);

		if ($actionStep === null) {
			return JsonResponse::error('Action step not found', Http::STATUS_NOT_FOUND);
		}

		$actionStep = $this->actionStepService->update($actionStep, $name, $parameter);

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
			$this->actionStepService->delete($id, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Action step not found', Http::STATUS_NOT_FOUND);
		}
	}

	public function swapOrder(int $id, int $newOrder): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->actionStepService->swapOrder($id, $newOrder);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Action step not found', Http::STATUS_NOT_FOUND);
		} catch (ServiceException $e) {
			return JsonResponse::fail($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}
	}

}
