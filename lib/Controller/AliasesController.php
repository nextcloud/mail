<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AliasesController extends Controller {
	private AliasesService $aliasService;
	private string $currentUserId;
	private DelegationService $delegationService;

	public function __construct(string $appName,
		IRequest $request,
		AliasesService $aliasesService,
		string $userId,
		DelegationService $delegationService) {
		parent::__construct($appName, $request);
		$this->aliasService = $aliasesService;
		$this->currentUserId = $userId;
		$this->delegationService = $delegationService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function index(int $accountId): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->currentUserId);
		return new JSONResponse($this->aliasService->findAll($accountId, $effectiveUserId));
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @return never
	 */
	#[TrapError]
	public function show() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function update(int $id,
		string $alias,
		string $aliasName,
		?int $smimeCertificateId = null): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->currentUserId);
		$alias = $this->aliasService->update(
			$effectiveUserId,
			$id,
			$alias,
			$aliasName,
			$smimeCertificateId,
		);
		$this->delegationService->logDelegatedAction("$this->currentUserId updated alias: $id on behalf of $effectiveUserId");
		return new JSONResponse($alias);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	#[TrapError]
	public function destroy(int $id): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->currentUserId);
		$alias = $this->aliasService->delete($effectiveUserId, $id);
		$this->delegationService->logDelegatedAction("$this->currentUserId deleted alias: $id on behalf of $effectiveUserId");
		return new JSONResponse($alias);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->currentUserId);
		$alias = $this->aliasService->create($effectiveUserId, $accountId, $alias, $aliasName);
		$id = $alias->getId();
		$this->delegationService->logDelegatedAction("$this->currentUserId created alias: $id  on behalf of $effectiveUserId");
		return new JSONResponse(
			$alias,
			Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->currentUserId);
		$alias = $this->aliasService->updateSignature($effectiveUserId, $id, $signature);
		$this->delegationService->logDelegatedAction("$this->currentUserId updated alias: $id 's signature on behalf of $effectiveUserId");
		return new JSONResponse($alias);
	}
}
