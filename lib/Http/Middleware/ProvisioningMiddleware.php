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
 *
 */

namespace OCA\Mail\Http\Middleware;

use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Middleware;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\Authentication\LoginCredentials\IStore as ICredentialStore;
use OCP\ILogger;
use OCP\IUserSession;

class ProvisioningMiddleware extends Middleware {

	/** @var IUserSession */
	private $userSession;

	/** @var ICredentialStore */
	private $credentialStore;

	/** @var ProvisioningManager */
	private $provisioningManager;

	/** @var ILogger */
	private $logger;

	public function __construct(IUserSession $userSession,
								ICredentialStore $credentialStore,
								ProvisioningManager $provisioningManager,
								ILogger $logger) {
		$this->userSession = $userSession;
		$this->credentialStore = $credentialStore;
		$this->provisioningManager = $provisioningManager;
		$this->logger = $logger;
	}

	public function beforeController($controller, $methodName) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			// Nothing to update
			return;
		}
		$config = $this->provisioningManager->getConfig();
		if ($config === null || !$config->isActive()) {
			return;
		}
		try {
			$this->provisioningManager->provisionSingleUser($config, $user);
			$this->provisioningManager->updatePassword(
				$user,
				$this->credentialStore->getLoginCredentials()->getPassword()
			);
		} catch (CredentialsUnavailableException|PasswordUnavailableException $e) {
			// Nothing to update
			return;
		}
	}
}
