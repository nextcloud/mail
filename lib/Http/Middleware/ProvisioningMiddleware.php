<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http\Middleware;

use OCP\AppFramework\Middleware;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\IUserSession;

class ProvisioningMiddleware extends Middleware {
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IUserSession $userSession,
		private readonly \OCP\Authentication\LoginCredentials\IStore $credentialStore,
		private readonly \OCA\Mail\Service\Provisioning\Manager $provisioningManager
	) {
		$this->userSession = $userSession;
	}

	#[\Override]
	public function beforeController($controller, $methodName): void {
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
		} catch (CredentialsUnavailableException|PasswordUnavailableException) {
			// Nothing to update
			return;
		}
	}
}
