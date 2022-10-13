<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Service\AutoCompletion\AutoCompleteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

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
	 * @TrapError
	 *
	 * @param string $term
	 * @return JSONResponse
	 */
	public function index(string $term): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([]);
		}

		return (new JSONResponse($this->service->findMatches($this->userId, $term)))
			->cacheFor(5 * 60, false, true);
	}
}
