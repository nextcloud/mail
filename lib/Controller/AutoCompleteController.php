<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AutoCompleteController extends Controller {
	private AutoCompleteService $service;
	private ?string $userId;

	public function __construct(string $appName,
		IRequest $request,
		AutoCompleteService $service,
		?string $userId) {
		parent::__construct($appName, $request);

		$this->service = $service;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $term
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function index(string $term): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([]);
		}

		return (new JSONResponse($this->service->findMatches($this->userId, $term)))
			->cacheFor(5 * 60, false, true);
	}
}
