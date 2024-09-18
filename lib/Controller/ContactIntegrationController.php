<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\ContactIntegration\ContactIntegrationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ContactIntegrationController extends Controller {
	private ContactIntegrationService $service;

	public function __construct(string $appName,
		IRequest $request,
		ContactIntegrationService $service) {
		parent::__construct($appName, $request);

		$this->service = $service;
	}

	/**
	 *
	 * @param string $mail
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function match(string $mail): JSONResponse {
		return (new JSONResponse($this->service->findMatches($mail)))->cacheFor(60 * 60, false, true);
	}

	/**
	 *
	 * @param string $uid
	 * @param string $mail
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function addMail(?string $uid = null, ?string $mail = null): JSONResponse {
		$res = $this->service->addEMailToContact($uid, $mail);
		if ($res === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($res);
	}

	#[TrapError]
	#[NoAdminRequired]
	public function newContact(?string $contactName = null, ?string $mail = null): JSONResponse {
		$res = $this->service->newContact($contactName, $mail);
		if ($res === null) {
			return new JSONResponse([], Http::STATUS_NOT_ACCEPTABLE);
		}
		return new JSONResponse($res);
	}

	/**
	 *
	 * @param string $term
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function autoComplete(string $term): JSONResponse {
		$res = $this->service->autoComplete($term);
		return (new JSONResponse($res))->cacheFor(60 * 60, false, true);
	}
}
