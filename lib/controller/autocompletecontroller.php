<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
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
		return $this->service->findMathes($term);
	}

}
