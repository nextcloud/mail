<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Http\JsonResponse as HttpJsonResponse;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use function array_merge;

class SettingsController extends Controller {

	/** @var ProvisioningManager */
	private $provisioningManager;

	public function __construct(IRequest $request,
								ProvisioningManager $provisioningManager) {
		parent::__construct(Application::APP_ID, $request);
		$this->provisioningManager = $provisioningManager;
	}

	public function index(): JSONResponse {
		$provisionings = $this->provisioningManager->getConfigs();
		return new JSONResponse($provisionings);
	}

	public function provision() : JSONResponse {
		$count = $this->provisioningManager->provision();
		return new JSONResponse(['count' => $count]);
	}

	public function createProvisioning(array $data): JSONResponse {
		try {
			$this->provisioningManager->newProvisioning($data);
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		}

		return new JSONResponse([]);
	}

	public function updateProvisioning(int $id, array $data): JSONResponse {
		try {
			$this->provisioningManager->updateProvisioning(array_merge(
				$data,
				['id' => $id]
			));
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		}

		return new JSONResponse([]);
	}

	public function deprovision(int $id): JSONResponse {
		$provisioning = $this->provisioningManager->getConfigById($id);

		if ($provisioning !== null) {
			$this->provisioningManager->deprovision($provisioning);
		}

		return new JSONResponse([]);
	}
}
