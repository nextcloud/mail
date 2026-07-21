<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\GovernanceLabelService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\Route;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class GovernanceLabelsController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private GovernanceLabelService $governanceLabelService,
	) {
		parent::__construct($appName, $request);
	}

	#[TrapError]
	#[NoAdminRequired]
	#[Route(Route::TYPE_FRONTPAGE, verb: 'GET', url: '/api/governance/labels')]
	public function index(): JsonResponse {
		return JsonResponse::success(
			array_values($this->governanceLabelService->getLabels(true)),
		);
	}

	#[TrapError]
	#[NoAdminRequired]
	#[Route(Route::TYPE_FRONTPAGE, verb: 'GET', url: '/api/governance/labels/{id}')]
	public function show(string $id): JsonResponse {
		$label = $this->governanceLabelService->getLabel($id);
		if ($label === null) {
			return JsonResponse::fail([], Http::STATUS_NOT_FOUND);
		}

		return JsonResponse::success($label);
	}
}
