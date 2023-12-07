<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

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
