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
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ProvisioningMiddleware extends Middleware {
	/** @var IUserSession */
	private $userSession;

	/** @var ICredentialStore */
	private $credentialStore;

	/** @var ProvisioningManager */
	private $provisioningManager;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IUserSession $userSession,
								ICredentialStore $credentialStore,
								ProvisioningManager $provisioningManager,
								LoggerInterface $logger) {
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
		$configs = $this->provisioningManager->getConfigs();
		if (empty($configs)) {
			return null;
		}
		try {
			$this->provisioningManager->provisionSingleUser($configs, $user);
			$password = $this->credentialStore->getLoginCredentials()->getPassword();
			// FIXME: Need to check for an empty string here too?
			// The password is empty (and not null) when using WebAuthn passwordless login.
			// Maybe research other providers as well.
			// Ref \OCA\Mail\Controller\PageController::index()
			//     -> inital state for password-is-unavailable
			if ($password === null) {
				// Nothing to update, might be passwordless signin
				$this->logger->debug('No password set for ' . $user->getUID());
				return;
			}
			$this->provisioningManager->updatePassword(
				$user,
				$password
			);
		} catch (CredentialsUnavailableException | PasswordUnavailableException $e) {
			// Nothing to update
			return;
		}
	}
}
