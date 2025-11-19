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
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AliasesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly AliasesService $aliasService,
		private readonly string $currentUserId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 */
	#[TrapError]
	public function index(int $accountId): JSONResponse {
		return new JSONResponse($this->aliasService->findAll($accountId, $this->currentUserId));
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
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function destroy(int $id): JSONResponse {
		return new JSONResponse($this->aliasService->delete($this->currentUserId, $id));
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		return new JSONResponse(
			$this->aliasService->create($this->currentUserId, $accountId, $alias, $aliasName),
			Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		return new JSONResponse($this->aliasService->updateSignature($this->currentUserId, $id, $signature));
	}
}
