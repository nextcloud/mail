<?php

declare(strict_types=1);

/**
 * @author Pierre Gordon <pierregordon@protonmail.com>
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

use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SieveService;
use OCA\Mail\Service\Sieve\Script;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class SieveController  extends Controller
{

	/** @var IUser */
	private $currentUser;

	/** @var SieveService */
	private $sieveService;

	/** @var AccountService */
	private $accountService;

	public function __construct($appName, IRequest $request, IUserSession $userSession,
								SieveService $filtersService, AccountService $accountService)
	{
		parent::__construct($appName, $request);

		$this->currentUser = $userSession->getUser();
		$this->sieveService = $filtersService;
		$this->accountService = $accountService;

	}

	/**
	 * @param int $accountId
	 * @return JSONResponse
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	public function index(int $accountId): JSONResponse
	{
		return new JSONResponse($this->getSieveService($accountId)->getScriptNames());
	}

	/**
	 * @param int $accountId
	 * @param string $script
	 * @param string $script_name
	 * @return JSONResponse
	 * @throws \Horde\ManageSieve\Exception
	 */
	public function create(int $accountId, string $script, string $script_name = null): JSONResponse
	{
		return new JSONResponse(
			$this->getSieveService($accountId)->createScript($script, $script_name)
		);
	}

	/**
	 * @param int $accountId
	 * @param string $id
	 * @return JSONResponse
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	public function show(int $accountId, string $id): JSONResponse
	{
		return new JSONResponse($this->getSieveService($accountId)->getScript($id));
	}

	/**
	 * @param int $accountId
	 * @param string $scriptName
	 * @return JSONResponse
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	public function setActiveScript(int $accountId, string $scriptName): JSONResponse
	{
		return new JSONResponse($this->getSieveService($accountId)->setActiveScript($scriptName));
	}

	/**
	 * @param int $accountId
	 * @return SieveService
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	private function getSieveService(int $accountId): SieveService
	{
		$account = $this->accountService->find($this->currentUser->getUID(), $accountId);

		return $this->sieveService->setAccount($account);
	}
}
