<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Exception\DelegationExistsException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DelegationController extends Controller {
	private ?string $currentUserId;

	public function __construct(
		string $appName,
		IRequest $request,
		private DelegationService $delegationService,
		private AccountService $accountService,
		private IUserManager $userManager,
		?string $UserId,
	) {
		parent::__construct($appName, $request);
		$this->currentUserId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 */
	#[TrapError]
	public function getDelegatedUsers(int $accountId): JSONResponse {
		$account = $this->accountService->findById($accountId);
		if ($account->getUserId() !== $this->currentUserId) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		return new JSONResponse(
			$this->delegationService->findDelegatedToUsersForAccount($accountId)
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $userId
	 * @return JSONResponse
	 */
	#[TrapError]
	public function delegate(int $accountId, string $userId): JSONResponse {

		$account = $this->accountService->findById($accountId);
		if ($this->currentUserId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		if ($account->getUserId() !== $this->currentUserId) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		if ($userId === $this->currentUserId) {
			return new JSONResponse(['message' => 'Cannot delegate to yourself'], Http::STATUS_BAD_REQUEST);
		}

		if (!$this->userManager->userExists($userId)) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		try {
			$delegation = $this->delegationService->delegate($account, $userId, $this->currentUserId);
		} catch (DelegationExistsException) {
			return new JSONResponse(['message' => 'Delegation already exists'], Http::STATUS_CONFLICT);
		}

		return new JSONResponse($delegation, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $userId
	 * @return JSONResponse
	 */
	#[TrapError]
	public function unDelegate(int $accountId, string $userId): JSONResponse {
		$account = $this->accountService->findById($accountId);

		if ($this->currentUserId === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		if ($account->getUserId() !== $this->currentUserId) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$this->delegationService->unDelegate($account, $userId, $this->currentUserId);
		return new JSONResponse([], Http::STATUS_OK);
	}
}
