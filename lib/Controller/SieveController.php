<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use Horde\ManageSieve\Exception as ManagesieveException;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Http\JsonResponse as MailJsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\SieveService;
use OCA\Mail\Sieve\SieveClientFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;

class SieveController extends Controller {
	private MailAccountMapper $mailAccountMapper;
	private SieveClientFactory $sieveClientFactory;
	private string $currentUserId;
	private ICrypto $crypto;
	private IRemoteHostValidator $hostValidator;
	private LoggerInterface $logger;

	public function __construct(IRequest $request,
		string $UserId,
		MailAccountMapper $mailAccountMapper,
		SieveClientFactory $sieveClientFactory,
		ICrypto $crypto,
		IRemoteHostValidator $hostValidator,
		LoggerInterface $logger,
		private SieveService $sieveService,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUserId = $UserId;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->sieveClientFactory = $sieveClientFactory;
		$this->crypto = $crypto;
		$this->hostValidator = $hostValidator;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id account id
	 *
	 * @return JSONResponse
	 *
	 * @throws CouldNotConnectException
	 * @throws ClientException
	 * @throws ManagesieveException
	 */
	#[TrapError]
	public function getActiveScript(int $id): JSONResponse {
		$activeScript = $this->sieveService->getActiveScript($this->currentUserId, $id);
		return new JSONResponse([
			'scriptName' => $activeScript->getName(),
			'script' => $activeScript->getScript(),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id account id
	 * @param string $script
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws CouldNotConnectException
	 */
	#[TrapError]
	public function updateActiveScript(int $id, string $script): JSONResponse {
		try {
			$this->sieveService->updateActiveScript($this->currentUserId, $id, $script);
		} catch (ManagesieveException $e) {
			$this->logger->error('Installing sieve script failed: ' . $e->getMessage(), ['app' => 'mail', 'exception' => $e]);
			return new JSONResponse(data: ['message' => $e->getMessage()], statusCode: Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id account id
	 * @param bool $sieveEnabled
	 * @param string $sieveHost
	 * @param int $sievePort
	 * @param string $sieveUser
	 * @param string $sievePassword
	 * @param string $sieveSslMode
	 *
	 * @return JSONResponse
	 *
	 * @throws CouldNotConnectException
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function updateAccount(int $id,
		bool $sieveEnabled,
		string $sieveHost,
		int $sievePort,
		string $sieveUser,
		string $sievePassword,
		string $sieveSslMode
	): JSONResponse {
		if (!$this->hostValidator->isValid($sieveHost)) {
			return MailJsonResponse::fail(
				[
					'error' => 'CONNECTION_ERROR',
					'service' => 'ManageSieve',
					'host' => $sieveHost,
					'port' => $sievePort,
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
		$mailAccount = $this->mailAccountMapper->find($this->currentUserId, $id);

		if ($sieveEnabled === false) {
			$mailAccount->setSieveEnabled(false);
			$mailAccount->setSieveHost(null);
			$mailAccount->setSievePort(null);
			$mailAccount->setSieveUser(null);
			$mailAccount->setSievePassword(null);
			$mailAccount->setSieveSslMode(null);

			$this->mailAccountMapper->save($mailAccount);
			return new JSONResponse(['sieveEnabled' => $mailAccount->isSieveEnabled()]);
		}

		if (empty($sieveUser)) {
			$sieveUser = $mailAccount->getInboundUser();
		}

		if (empty($sievePassword)) {
			$sievePassword = $mailAccount->getInboundPassword();
		} else {
			$sievePassword = $this->crypto->encrypt($sievePassword);
		}

		try {
			$this->sieveClientFactory->createClient($sieveHost, $sievePort, $sieveUser, $sievePassword, $sieveSslMode);
		} catch (ManagesieveException $e) {
			throw new CouldNotConnectException($e, 'ManageSieve', $sieveHost, $sievePort);
		}

		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost($sieveHost);
		$mailAccount->setSievePort($sievePort);
		$mailAccount->setSieveUser($mailAccount->getInboundUser() === $sieveUser ? null : $sieveUser);
		$mailAccount->setSievePassword($mailAccount->getInboundPassword() === $sievePassword ? null : $sievePassword);
		$mailAccount->setSieveSslMode($sieveSslMode);

		$this->mailAccountMapper->save($mailAccount);
		return new JSONResponse(['sieveEnabled' => $mailAccount->isSieveEnabled()]);
	}
}
