<?php

declare(strict_types=1);

/**
 * @author Kristian Lebold <kristian@lebold.info>
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

use OCA\Mail\Service\ContactIntegration\ContactIntegrationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ContactIntegrationController extends Controller {
	private ContactIntegrationService $service;

	public function __construct(string $appName,
								IRequest $request,
								ContactIntegrationService $service) {
		parent::__construct($appName, $request);

		$this->service = $service;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $mail
	 * @return JSONResponse
	 */
	public function match(string $mail): JSONResponse {
		return (new JSONResponse($this->service->findMatches($mail)))->cacheFor(60 * 60, false, true);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $uid
	 * @param string $mail
	 * @return JSONResponse
	 */
	public function addMail(string $uid = null, string $mail = null): JSONResponse {
		$res = $this->service->addEMailToContact($uid, $mail);
		if ($res === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($res);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $name
	 * @param string $mail
	 * @return JSONResponse
	 */
	public function newContact(string $contactName = null, string $mail = null): JSONResponse {
		$res = $this->service->newContact($contactName, $mail);
		if ($res === null) {
			return new JSONResponse([], Http::STATUS_NOT_ACCEPTABLE);
		}
		return new JSONResponse($res);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $term
	 * @return JSONResponse
	 */
	public function autoComplete(string $term): JSONResponse {
		$res = $this->service->autoComplete($term);
		return (new JSONResponse($res))->cacheFor(60 * 60, false, true);
	}
}
