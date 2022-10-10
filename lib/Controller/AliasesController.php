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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class AliasesController extends Controller {
	private AliasesService $aliasService;
	private string $currentUserId;

	public function __construct(string $appName,
								IRequest $request,
								AliasesService $aliasesService,
								string $UserId) {
		parent::__construct($appName, $request);
		$this->aliasService = $aliasesService;
		$this->currentUserId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 */
	public function index(int $accountId): JSONResponse {
		return new JSONResponse($this->aliasService->findAll($accountId, $this->currentUserId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @TrapError
	 *
	 * @return never
	 */
	public function show() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function update(int $id, string $alias, string $aliasName): JSONResponse {
		return new JSONResponse($this->aliasService->update($this->currentUserId, $id, $alias, $aliasName));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy(int $id): JSONResponse {
		return new JSONResponse($this->aliasService->delete($this->currentUserId, $id));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	public function create(int $accountId, string $alias, string $aliasName): JSONResponse {
		return new JSONResponse(
			$this->aliasService->create($this->currentUserId, $accountId, $alias, $aliasName),
			Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 */
	public function updateSignature(int $id, string $signature = null): JSONResponse {
		return new JSONResponse($this->aliasService->updateSignature($this->currentUserId, $id, $signature));
	}
}
