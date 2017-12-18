<?php

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
use OCA\Mail\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;

class PreferencesController extends Controller {

	/** @var IUserPreferences */
	private $userPreference;

	/**
	 * @param IRequest $request
	 * @param IUserPreferences $userPreference
	 */
	public function __construct(IRequest $request, IUserPreferences $userPreference) {
		parent::__construct('mail', $request);
		$this->userPreference = $userPreference;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return JSONResponse
	 */
	public function show($id) {
		return new JSONResponse([
			'value' => $this->userPreference->getPreference($id)
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $key
	 * @param string $value
	 * @return JSONResponse
	 */
	public function update($key, $value) {
		if (is_null($key) || is_null($value)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$this->userPreference->setPreference($key, $value);

		return new JSONResponse();
	}

}
