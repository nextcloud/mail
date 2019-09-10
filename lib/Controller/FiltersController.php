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

use OCA\Mail\Exception\AccountException;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\FiltersService;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class FiltersController  extends Controller
{

	/** @var IUser */
	private $currentUser;

	/** @var FiltersService */
	private $filtersService;

	/** @var AccountService */
	private $accountService;

	public function __construct($appName, IRequest $request, IUserSession $userSession,
								FiltersService $filtersService, AccountService $accountService)
	{
		parent::__construct($appName, $request);

		$this->currentUser = $userSession->getUser();
		$this->filtersService = $filtersService;
		$this->accountService = $accountService;

	}

	/**
	 * @param int $accountId
	 * @return JSONResponse
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	public function getScripts(int $accountId): JSONResponse
	{
		return new JSONResponse($this->getFiltersService($accountId)->getScriptNames());
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
		return new JSONResponse($this->getFiltersService($accountId)->setActiveScript($scriptName));
	}

	/**
	 * @param int $accountId
	 * @return FiltersService
	 * @throws AccountException
	 * @throws \Horde\ManageSieve\Exception
	 */
	private function getFiltersService(int $accountId): FiltersService
	{
		$account = $this->accountService->find($this->currentUser->getUID(), $accountId);

		return $this->filtersService->setAccount($account);
	}
}
