<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		if ($configs === []) {
			return;
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
				$password,
				$configs
			);
		} catch (CredentialsUnavailableException | PasswordUnavailableException $e) {
			// Nothing to update
			return;
		}
	}
}
