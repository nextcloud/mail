<?php

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

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;

class AutoCompleteController extends Controller {

	/** @var AutoCompleteService */
	private $service;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AutoCompleteService $service
	 * @param string $UserId
	 */
	public function __construct($appName, IRequest $request,
		AutoCompleteService $service) {
		parent::__construct($appName, $request);
		$this->service = $service;
	}

	/**
	 * @NoAdminRequired
	 * @param string $term
	 * @return array
	 */
	public function index($term) {
		return $this->service->findMatches($term);
	}

}
