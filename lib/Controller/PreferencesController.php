<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class PreferencesController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly IUserPreferences $userPreference,
		private readonly string $userId
	) {
		parent::__construct('mail', $request);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function show(string $id): JSONResponse {
		return new JSONResponse([
			'value' => $this->userPreference->getPreference($this->userId, $id)
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $key
	 * @param string $value
	 * @throws ClientException
	 */
	#[TrapError]
	public function update($key, $value): JSONResponse {
		if (is_null($key) || is_null($value)) {
			throw new ClientException('key or value missing');
		}


		$newValue = $this->userPreference->setPreference($this->userId, $key, $value);

		return new JSONResponse([
			'value' => $newValue,
		]);
	}
}
