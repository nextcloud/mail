<?php

declare(strict_types=1);

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class AliasesController extends Controller {

	/** @var AliasesService */
	private $aliasService;

	/** @var IUser */
	private $currentUser;

	public function __construct(string $appName,
								IRequest $request,
								AliasesService $aliasesService,
								IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->aliasService = $aliasesService;
		$this->currentUser = $userSession->getUser();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 */
	public function index($accountId): JSONResponse {
		return new JSONResponse($this->aliasService->findAll($accountId, $this->currentUser->getUID()));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function show() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function update() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id): JSONResponse {
		return new JSONResponse($this->aliasService->delete($id, $this->currentUser->getUID()));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 * @return JSONResponse
	 */
	public function create($accountId, $alias, $aliasName): JSONResponse {
		return new JSONResponse($this->aliasService->create($accountId, $alias, $aliasName), Http::STATUS_CREATED);
	}
}
