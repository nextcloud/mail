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

	/** @var AutoCompleteService */
	private $service;

	public function __construct(string $appName,
								IRequest $request,
								AutoCompleteService $service) {
		parent::__construct($appName, $request);

		$this->service = $service;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $term
	 * @return JSONResponse
	 */
	public function index(string $term): JSONResponse {
		return new JSONResponse($this->service->findMatches($term));
	}
}
