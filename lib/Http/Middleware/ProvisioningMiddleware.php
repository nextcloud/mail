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

class ProvisioningMiddleware extends Middleware {
	/** @var IUserSession */
	private $userSession;

	/** @var ICredentialStore */
	private $credentialStore;

	/** @var ProvisioningManager */
	private $provisioningManager;

	public function __construct(IUserSession $userSession,
		ICredentialStore $credentialStore,
		ProvisioningManager $provisioningManager) {
		$this->userSession = $userSession;
		$this->credentialStore = $credentialStore;
		$this->provisioningManager = $provisioningManager;
	}

	#[\Override]
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
			$this->provisioningManager->updatePassword(
				$user,
				$password,
				$configs
			);
		} catch (CredentialsUnavailableException|PasswordUnavailableException $e) {
			// Nothing to update
			return;
		}
	}
}
