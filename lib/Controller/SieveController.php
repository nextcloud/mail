<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
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
	#[NoAdminRequired]
	public function getActiveScript(int $id): JSONResponse {
		$activeScript = $this->sieveService->getActiveScript($this->currentUserId, $id);
		return new JSONResponse([
			'scriptName' => $activeScript->getName(),
			'script' => $activeScript->getScript(),
		]);
	}

	/**
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
	#[NoAdminRequired]
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
	#[NoAdminRequired]
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
