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
	public function getOwnTextBlocks(): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		$textBlocks = $this->textBlockService->findAll($this->uid);

		return JsonResponse::success($textBlocks);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function getSharedTextBlocks(): JsonResponse {
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
			return JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		}

		$this->textBlockService->update($id, $this->uid, $title, $content);

		return JsonResponse::success($textBlock, Http::STATUS_OK);
	}

	public function delete(int $id): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->textBlockService->delete($id, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('TextBlock not found', Http::STATUS_NOT_FOUND);
		}
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
	public function share(int $textBlockId, string $shareWith, string $type): JsonResponse {
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
	 *
	 * @return JsonResponse
	 */
	public function getShares(int $id): JsonResponse {
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

	/**
	 * @NoAdminRequired
	 * @param int $textBlockId
	 * @param string $shareWith
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function deleteShare(int $textBlockId, string $shareWith): JsonResponse {
		if ($this->uid === null) {
			return JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		}

		$textBlock = $this->textBlockService->find($textBlockId, $this->uid);

		if ($textBlock === null) {
			return JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		}

		$this->textBlockService->unshare($textBlockId, $shareWith);

		return JsonResponse::success();
	}

}
