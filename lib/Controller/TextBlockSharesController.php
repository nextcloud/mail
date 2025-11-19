<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Exception\UserNotFoundException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\TextBlockService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class TextBlockSharesController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly ?string $uid,
		private readonly TextBlockService $textBlockService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function index(): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$textBlocks = $this->textBlockService->findAllSharedWithMe($this->uid);
		} catch (UserNotFoundException) {
			return JsonResponse::error('Sharee not found', Http::STATUS_UNAUTHORIZED);
		}

		return JsonResponse::success($textBlocks);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function create(int $textBlockId, string $shareWith, string $type): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->textBlockService->find($textBlockId, $this->uid);
		} catch (DoesNotExistException) {
			return JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
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
	 */
	#[TrapError]
	public function destroy(int $id, string $shareWith): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->textBlockService->find($id, $this->uid);
		} catch (DoesNotExistException) {
			return JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
		}

		$this->textBlockService->unshare($id, $shareWith);

		return JsonResponse::success();
	}

	/**
	 * @NoAdminRequired
	 */
	public function getTextBlockShares(int $id): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->textBlockService->find($id, $this->uid);
		} catch (DoesNotExistException) {
			return JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
		}

		$shares = $this->textBlockService->getShares($id);

		return JsonResponse::success($shares);
	}

}
