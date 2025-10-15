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
use OCA\Mail\Service\TextBlockService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class TextBlockController extends Controller {
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
		$textBlocks = $this->textBlockService->findAll($this->uid);

		return JsonResponse::success($textBlocks);
	}

	/**
	 * @NoAdminRequired
	 * @param string $title
	 * @param string $content
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function create(string $title, string $content): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$textBlock = $this->textBlockService->create($this->uid, $title, $content);

		return JsonResponse::success($textBlock, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(int $id, string $title, string $content): JsonResponse {

		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$textBlock = $this->textBlockService->find($id, $this->uid);

		if ($textBlock === null) {
			return JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
		}

		$textBlock = $this->textBlockService->update($textBlock, $this->uid, $title, $content);

		return JsonResponse::success($textBlock, Http::STATUS_OK);
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
			$this->textBlockService->delete($id, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Text block not found', Http::STATUS_NOT_FOUND);
		}
	}

}
