<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AuditLog\Operation;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	#[TrapError]
	public function index(int $accountId): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
		return new JSONResponse($this->aliasService->findAll($accountId, $effectiveUserId));
	}

	/**
	 * @return never
	 */
	#[NoAdminRequired]
	#[TrapError]
	public function show() {
		throw new NotImplemented();
	}

	#[NoAdminRequired]
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
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, Operation::DelegatedAliasUpdated, '%s updated alias: %s on behalf of %s', ['aliasId' => $id]);
		return new JSONResponse($alias);
	}

	/**
	 * @param int $id
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	#[TrapError]
	public function destroy(int $id): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->userId);
		$alias = $this->aliasService->delete($effectiveUserId, $id);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, Operation::DelegatedAliasDeleted, '%s deleted alias: %s on behalf of %s', ['aliasId' => $id]);
		return new JSONResponse($alias);
	}

	/**
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	#[NoAdminRequired]
	#[TrapError]
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
		$alias = $this->aliasService->create($effectiveUserId, $accountId, $alias, $aliasName);
		$id = $alias->getId();
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, Operation::DelegatedAliasCreated, '%s created alias: %s  on behalf of %s', ['aliasId' => $id]);
		return new JSONResponse(
			$alias,
			Http::STATUS_CREATED
		);
	}

	/**
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	#[NoAdminRequired]
	#[TrapError]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAliasUserId($id, $this->userId);
		$alias = $this->aliasService->updateSignature($effectiveUserId, $id, $signature);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, Operation::DelegatedAliasSignatureUpdated, "%s updated alias: %s 's signature on behalf of %s", ['aliasId' => $id]);
		return new JSONResponse($alias);
	}
}
