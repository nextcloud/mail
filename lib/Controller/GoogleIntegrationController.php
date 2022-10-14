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
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Integration\GoogleIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;

class GoogleIntegrationController extends Controller {
	private GoogleIntegration $googleIntegration;

	public function __construct(IRequest $request,
								GoogleIntegration $googleIntegration) {
		parent::__construct(Application::APP_ID, $request);
		$this->googleIntegration = $googleIntegration;
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
}
