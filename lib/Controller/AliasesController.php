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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AliasesController extends Controller {
	private AliasesService $aliasService;
	private string $currentUserId;

	public function __construct(string $appName,
		IRequest $request,
		AliasesService $aliasesService,
		string $UserId) {
		parent::__construct($appName, $request);
		$this->aliasService = $aliasesService;
		$this->currentUserId = $UserId;
	}

	/**
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function index(int $accountId): JSONResponse {
		return new JSONResponse($this->aliasService->findAll($accountId, $this->currentUserId));
	}

	/**
	 *
	 * @return never
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function show() {
		throw new NotImplemented();
	}

	#[TrapError]
	#[NoAdminRequired]
	public function update(int    $id,
		string $alias,
		string $aliasName,
		?int   $smimeCertificateId = null): JSONResponse {
		return new JSONResponse(
			$this->aliasService->update(
				$this->currentUserId,
				$id,
				$alias,
				$aliasName,
				$smimeCertificateId,
			)
		);
	}

	/**
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function destroy(int $id): JSONResponse {
		return new JSONResponse($this->aliasService->delete($this->currentUserId, $id));
	}

	/**
	 *
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		return new JSONResponse(
			$this->aliasService->create($this->currentUserId, $accountId, $alias, $aliasName),
			Http::STATUS_CREATED
		);
	}

	/**
	 *
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		return new JSONResponse($this->aliasService->updateSignature($this->currentUserId, $id, $signature));
	}
}
