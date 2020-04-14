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
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class SettingsController extends Controller {

	/** @var ProvisioningManager */
	private $provisioningManager;

	public function __construct(IRequest $request,
								ProvisioningManager $provisioningManager) {
		parent::__construct(Application::APP_ID, $request);
		$this->provisioningManager = $provisioningManager;
	}

	public function provisioning(string $emailTemplate,
								 string $imapUser,
								 string $imapHost,
								 int $imapPort,
								 string $imapSslMode,
								 string $smtpUser,
								 string $smtpHost,
								 int $smtpPort,
								 string $smtpSslMode): JSONResponse {
		$this->provisioningManager->newProvisioning(
			$emailTemplate,
			$imapUser,
			$imapHost,
			$imapPort,
			$imapSslMode,
			$smtpUser,
			$smtpHost,
			$smtpPort,
			$smtpSslMode
		);

		return new JSONResponse(null);
	}

	public function deprovision(): JSONResponse {
		$this->provisioningManager->deprovision();

		return new JSONResponse(null);
	}
}
