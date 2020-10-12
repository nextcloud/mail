<?php

declare(strict_types=1);

/**
 * @author Holger Dehnhardt <holger@dehnhardt.org>
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

use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SieveService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ILogger;

class SieveController extends Controller {

	/** @var AccountService */
	private $accountService;
	/** @var UserId */
	private $currentUserId;
	/** @var SieveService */
	private $sieveService;
	/** @var ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string $UserId
	 * @param SieveService $sieveService
	 * @param ILogger $logger
	 */

	public function __construct(string $appName,
								IRequest $request,
								AccountService $accountService,
								$UserId,
								SieveService $sieveService,
								ILogger $logger) {
		parent::__construct($appName, $request);

		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->sieveService = $sieveService;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param bool $sieveEnabled
	 * @param string $sieveHost
	 * @param int $sievePort
	 * @param string $sieveUSer
	 * @param string $sieveSslMode
	 * @param string $sievePassword
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function updateSieveAccount(int $accountId, bool $sieveEnabled, string $sieveHost, int $sievePort, string $sieveUser, string $sieveSslMode, string $sievePassword): JSONResponse {
		$this->logger->info("update account (from SieveController");
		$account = $this->accountService->find($this->currentUserId, $accountId);

		$params = [
			'host' => $sieveHost,
			'port' => $sievePort,
			'user' => $sieveUser,
			'password' => $sievePassword,
			'secure' => $sieveSslMode,
		];

		try {
			$ret = $this->sieveService->updateSieveAccount($account, $params);
			$message = "account modified successfully";
		} catch (ServiceException $e) {
			$ret = false;
			$message = $e->getMessage();
		} catch (\Throwable $e) {
			throw new ServiceException($e->getMessage(), 0);
		}

		return new JSONResponse(
			['sieveEnabled' => $ret,
				'message' => $message]
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function listScripts(int $accountId) {
		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
			$scripts = $this->sieveService->listScripts($account);
		} catch (ServiceException $e) {
			$message = $e->getMessage();
		}
		return new JSONResponse($scripts);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $scriptName
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function getScriptContent(int $accountId, string $scriptName) {
		try {
			$account = $this->accountService->find($this->currentUserId, $accountId);
			$scriptContent = $this->sieveService->getScriptContent($account, $scriptName);
		} catch (ServiceException $e) {
			$message = $e->getMessage();
		}
		return new JSONResponse(['scriptContent' => $scriptContent]);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $scriptName
	 * @param bool $install
	 * @param array $scriptContent
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function setScriptContent(int $accountId, string $scriptName, bool $install, array $scriptContent) {
		$this->logger->debug("SieveController: setScriptContent");
		$account = $this->accountService->find($this->currentUserId, $accountId);
		$this->sieveService->setScriptContent($account, $scriptName, $install, $scriptContent);
		return new JSONResponse();
	}
}
