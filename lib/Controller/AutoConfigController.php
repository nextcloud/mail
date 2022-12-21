<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Controller;

use Horde_Mail_Rfc822_Address;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\IspDb;
use OCA\Mail\Service\AutoConfig\MxRecord;
use OCA\Mail\Validation\RemoteHostValidator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;
use function in_array;

class AutoConfigController extends Controller {
	private IspDb $ispDb;
	private MxRecord $mxRecord;
	private ConnectivityTester $connectivityTester;
	private RemoteHostValidator $hostValidator;

	public function __construct(IRequest $request,
								IspDb $ispDb,
								MxRecord $mxRecord,
								ConnectivityTester $connectivityTester,
								RemoteHostValidator $hostValidator) {
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
	 * @TrapError
	 *
	 * @return JsonResponse
	 */
	public function queryIspdb(string $email): JsonResponse {
		$rfc822Address = new Horde_Mail_Rfc822_Address($email);
		if (!$rfc822Address->valid || !$this->hostValidator->isValid($rfc822Address->host)) {
			return JsonResponse::fail('Invalid email address', Http::STATUS_UNPROCESSABLE_ENTITY)
				->cacheFor(60 * 60, false, true);
		}
		$config = $this->ispDb->query($rfc822Address->host, $rfc822Address);
		return JsonResponse::success($config)->cacheFor(5 * 60, false, true);
	}

	/**
	 * @param string $email
	 *
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @return JsonResponse
	 */
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
	 * @TrapError
	 *
	 * @return JsonResponse
	 */
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
