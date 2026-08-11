<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Exception\ClientException;
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
	public function __construct(
		string $appName,
		IRequest $request,
		private AliasesService $aliasService,
		private string $userId,
		private DelegationService $delegationService,
	) {
		parent::__construct($appName, $request);
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
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
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
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->userId);
		$alias = $this->aliasService->update(
			$effectiveUserId,
			$id,
			$alias,
			$aliasName,
			$smimeCertificateId,
		);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId updated alias: $id on behalf of $effectiveUserId");
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
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->userId);
		$alias = $this->aliasService->delete($effectiveUserId, $id);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId deleted alias: $id on behalf of $effectiveUserId");
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
	 * @throws ClientException
	 */
	#[TrapError]
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
		$alias = $this->aliasService->create($effectiveUserId, $accountId, $alias, $aliasName);
		$id = $alias->getId();
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId created alias: $id  on behalf of $effectiveUserId");
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
	 * @throws ClientException
	 */
	#[TrapError]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->userId);
		$alias = $this->aliasService->updateSignature($effectiveUserId, $id, $signature);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId updated alias: $id 's signature on behalf of $effectiveUserId");
		return new JSONResponse($alias);
	}
}
