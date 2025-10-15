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
	private IUserPreferences $userPreference;
	private string $userId;

	/**
	 * @param IRequest $request
	 * @param IUserPreferences $userPreference
	 * @param string $UserId
	 */
	public function __construct(IRequest $request, IUserPreferences $userPreference, string $UserId) {
		parent::__construct('mail', $request);

		$this->userPreference = $userPreference;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return JSONResponse
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
	 * @return JSONResponse
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
