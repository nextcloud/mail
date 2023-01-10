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

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use function filter_var;

class GoogleIntegrationController extends Controller {
	private ?string $userId;
	private GoogleIntegration $googleIntegration;
	private AccountService $accountService;
	private LoggerInterface $logger;

	public function __construct(IRequest $request,
								?string $UserId,
								GoogleIntegration $googleIntegration,
								AccountService $accountService,
								LoggerInterface $logger) {
		parent::__construct(Application::APP_ID, $request);
		$this->userId = $UserId;
		$this->googleIntegration = $googleIntegration;
		$this->accountService = $accountService;
		$this->logger = $logger;
	}

	/**
	 * @param string $clientId
	 * @param string $clientSecret
	 *
	 * @return JsonResponse
	 */
	public function configure(string $clientId, string $clientSecret): JsonResponse {
		if (empty($clientId) || empty($clientSecret)) {
			return JsonResponse::fail(null, Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$this->googleIntegration->configure(
			$clientId,
			$clientSecret,
		);

		return JsonResponse::success([
			'clientId' => $clientId,
		]);
	}

	/*
	 * @return JsonResponse
	 */
	public function unlink(): JsonResponse {
		$this->googleIntegration->unlink();

		return JsonResponse::success([]);
	}

	/**
	 * @param int $id
	 * @param string|null $code
	 * @param string|null $state
	 * @param string|null $scope
	 * @param string|null $error
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function oauthRedirect(?string $code, ?string $state, ?string $scope, ?string $error): Response {
		if ($this->userId === null) {
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		if (!isset($code, $state, $scope)) {
			// TODO: handle error
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}
		if (!filter_var($state, FILTER_VALIDATE_INT)) {
			$this->logger->warning('Can not link Google account due to invalid state/account id {state}', [
				'state' => $state,
			]);
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		try {
			$account = $this->accountService->find(
				$this->userId,
				(int) $state,
			);
		} catch (ClientException $e) {
			$this->logger->warning('Attempted Google authentication redirect for account: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		$updated = $this->googleIntegration->finishConnect(
			$account,
			$code,
		);
		$this->accountService->update($updated->getMailAccount());

		return new StandaloneTemplateResponse(
			Application::APP_ID,
			'oauth_done',
			[],
			'guest',
		);
	}
}
