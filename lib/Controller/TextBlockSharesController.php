<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\TextBlockService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class TextBlockSharesController extends Controller {
	private ?string $uid;

	public function __construct(
		IRequest $request,
		?string $userId,
		private TextBlockService $textBlockService,
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
		try {
			$textBlocks = $this->textBlockService->findAllSharedWithMe($this->uid);
		} catch (DoesNotExistException $e) {
			return JsonResponse::error('Sharee not found', Http::STATUS_UNAUTHORIZED);
		}

		return JsonResponse::success($textBlocks);
	}

	/**
	 * @NoAdminRequired
	 * @param int $textBlockId
	 * @param string $shareWith
	 * @param string $type
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function create(int $textBlockId, string $shareWith, string $type): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$textBlock = $this->textBlockService->find($textBlockId, $this->uid);

		if ($textBlock === null) {
			return JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		}

		switch ($type) {
			case TextBlockShare::TYPE_USER:
				$this->textBlockService->share($textBlockId, $shareWith);
				return JsonResponse::success();
			case TextBlockShare::TYPE_GROUP:
				$this->textBlockService->shareWithGroup($textBlockId, $shareWith);
				return JsonResponse::success();
			default:
				return JsonResponse::fail('Invalid share type', Http::STATUS_BAD_REQUEST);
		}

	}
	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $shareWith
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function destroy(int $id, string $shareWith): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$textBlock = $this->textBlockService->find($id, $this->uid);

		if ($textBlock === null) {
			return JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		}

		$this->textBlockService->unshare($id, $shareWith);

		return JsonResponse::success();
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function getTextBlockShares(int $id): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$textBlock = $this->textBlockService->find($id, $this->uid);

		if ($textBlock === null) {
			return JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		}

		$shares = $this->textBlockService->getShares($id);

		return JsonResponse::success($shares);
	}

}
