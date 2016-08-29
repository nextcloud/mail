<?php
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

use OCA\Mail\Db\Alias;
use OCP\IRequest;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IUserSession;

class AliasesController extends Controller {

	/** @var AliasesService */
	private $aliasService;

	/**
	 * @var \OCP\IUser
	 */
	private $currentUser;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AliasesService $aliasesService
	 */
	public function __construct($appName, IRequest $request, AliasesService $aliasesService, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->aliasService = $aliasesService;
		$this->currentUser = $userSession->getUser();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $accountId
	 * @return Alias[]
	 */
	public function index($accountId) {
		return $this->aliasService->findAll($accountId, $this->currentUser->getUID());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function show() {
		$response = new JSONResponse();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update() {
		$response = new JSONResponse();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $id
	 * @return Alias[]
	 */
	public function destroy($id) {
		return $this->aliasService->delete($id, $this->currentUser->getUID());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 * @return Alias[]
	 */
	public function create($accountId, $alias, $aliasName) {
		return $this->aliasService->create($accountId, $alias, $aliasName);
	}
}
