<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Horde_Mail_Rfc822_Address;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\IspDb;
use OCA\Mail\Service\AutoConfig\MxRecord;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use function in_array;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AutoConfigController extends Controller {
	private IspDb $ispDb;
	private MxRecord $mxRecord;
	private ConnectivityTester $connectivityTester;
	private IRemoteHostValidator $hostValidator;

	public function __construct(IRequest $request,
		IspDb $ispDb,
		MxRecord $mxRecord,
		ConnectivityTester $connectivityTester,
		IRemoteHostValidator $hostValidator) {
		parent::__construct(Application::APP_ID, $request);
		$this->ispDb = $ispDb;
		$this->mxRecord = $mxRecord;
		$this->connectivityTester = $connectivityTester;
		$this->hostValidator = $hostValidator;
	}

	/**
	 * @param string $email
	 *
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=5, period=60)
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	#[UserRateLimit(limit: 5, period: 60)]
	public function queryIspdb(string $host, string $email): JsonResponse {
		$rfc822Address = new Horde_Mail_Rfc822_Address($email);
		if (!$rfc822Address->valid || !$this->hostValidator->isValid($host)) {
			return JsonResponse::fail('Invalid email address', Http::STATUS_UNPROCESSABLE_ENTITY)
				->cacheFor(60 * 60, false, true);
		}
		$config = $this->ispDb->query($host, $rfc822Address);
		return JsonResponse::success($config)->cacheFor(5 * 60, false, true);
	}

	/**
	 * @param string $email
	 *
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=5, period=60)
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	#[UserRateLimit(limit: 5, period: 60)]
	public function queryMx(string $email): JsonResponse {
		$rfc822Address = new Horde_Mail_Rfc822_Address($email);
		if (!$rfc822Address->valid || !$this->hostValidator->isValid($rfc822Address->host)) {
			return JsonResponse::fail('Invalid email address', Http::STATUS_UNPROCESSABLE_ENTITY)
				->cacheFor(60 * 60, false, true);
		}
		return JsonResponse::success(
			$this->mxRecord->query($rfc822Address->host),
		)->cacheFor(5 * 60, false, true);
	}

	/**
	 * @param string $host
	 * @param int $port
	 *
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=30, period=60)
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	#[UserRateLimit(limit: 30, period: 60)]
	public function testConnectivity(string $host, int $port): JsonResponse {
		if (!in_array($port, [143, 993, 465, 587])) {
			return JsonResponse::fail('Port not allowed');
		}
		if (!$this->hostValidator->isValid($host)) {
			return JsonResponse::success(false);
		}
		return JsonResponse::success(
			$this->connectivityTester->canConnect($host, $port),
		);
	}
}
