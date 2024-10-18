<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\SnippetShare;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\SnippetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;

class SnippetController extends Controller {
	private ?string $uid;

	public function __construct(
		IRequest $request,
		?string $userId,
		private SnippetService $snippetService,
	) {
		parent::__construct(Application::APP_ID, $request);

		$this->snippetService = $snippetService;
		$this->uid = $userId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function getOwnSnippets(): JsonResponse {
		$snippets = $this->snippetService->findAll($this->uid);

		return JsonResponse::success($snippets);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function getSharedSnippets(): JsonResponse {
		$snippets = $this->snippetService->findAllSharedWithMe($this->uid);

		return JsonResponse::success($snippets);
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
		$snippet = $this->snippetService->create($this->uid, $title, $content);

		return JsonResponse::success($snippet, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @param int $snippetId
	 * @param string $title
	 * @param string $content
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(int $snippetId, string $title, string $content): JsonResponse {
		
		$snippet = $this->snippetService->find($snippetId, $this->uid);

		if ($snippet === null) {
			return JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		}

		$this->snippetService->update($snippetId, $this->uid, $title, $content);

		return JsonResponse::success($snippet, Http::STATUS_OK);
	}

	public function delete($snippetId): JsonResponse {
		try {
			$this->snippetService->delete($snippetId, $this->uid);
			return JsonResponse::success();
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail('Snippet not found', Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @NoAdminRequired
	 * @param int $snippetId
	 * @param string $shareWith
	 * @param string $type
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function share(int $snippetId, string $shareWith, string $type): JsonResponse {
		$snippet = $this->snippetService->find($snippetId, $this->uid);

		if ($snippet === null) {
			return JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		}

		switch ($type) {
			case SnippetShare::TYPE_USER:
				$this->snippetService->share($snippetId, $shareWith);
				return JsonResponse::success();
			case SnippetShare::TYPE_GROUP:
				$this->snippetService->shareWithGroup($snippetId, $shareWith);
				return JsonResponse::success();
			default:
				return JsonResponse::fail('Invalid share type', Http::STATUS_BAD_REQUEST);
		}

	}

	public function getShares($id): JsonResponse {
		$snippet = $this->snippetService->find($snippetId, $this->uid);

		if ($snippet === null) {
			return JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		}

		$shares = $this->snippetService->getShares($this->uid, $snippetId);

		return JsonResponse::success($shares);
	}

	/**
	 * @NoAdminRequired
	 * @param int $snippetId
	 * @param string $shareWith
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function deleteShare(int $snippetId, string $shareWith): JsonResponse {
		$snippet = $this->snippetService->find($snippetId, $this->uid);

		if ($snippet === null) {
			return JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		}

		$this->snippetService->unshare($snippetId, $shareWith);

		return JsonResponse::success();
	}

}
