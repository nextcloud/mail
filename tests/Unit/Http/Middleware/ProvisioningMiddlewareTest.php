<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Http\Middleware;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\PageController;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Http\Middleware\ProvisioningMiddleware;
use OCA\Mail\Service\Provisioning\Manager;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;

class ProvisioningMiddlewareTest extends TestCase {
	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IStore|MockObject */
	private $credentialStore;

	/** @var Manager|MockObject */
	private $provisioningManager;

	/** @var ProvisioningMiddleware */
	private $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->credentialStore = $this->createMock(IStore::class);
		$this->provisioningManager = $this->createMock(Manager::class);

		$this->middleware = new ProvisioningMiddleware(
			$this->userSession,
			$this->credentialStore,
			$this->provisioningManager,
		);
	}

	public function testBeforeControllerNotLoggedIn() {
		$this->credentialStore->expects($this->never())
			->method('getLoginCredentials');
		$this->provisioningManager->expects($this->never())
			->method('updatePassword');

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}

	public function testBeforeControllerNoCredentialsAvailable() {
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$configs = [new Provisioning()];
		$this->provisioningManager->expects($this->once())
			->method('getConfigs')
			->willReturn($configs);
		$this->provisioningManager->expects($this->once())
			->method('provisionSingleUser')
			->with($configs, $user);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willThrowException($this->createMock(CredentialsUnavailableException::class));
		$this->provisioningManager->expects($this->never())
			->method('updatePassword');

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}

	public function testBeforeControllerNoPasswordAvailable() {
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$configs = [new Provisioning()];
		$this->provisioningManager->expects($this->once())
			->method('getConfigs')
			->willReturn($configs);
		$this->provisioningManager->expects($this->once())
			->method('provisionSingleUser')
			->with($configs, $user);
		$credentials = $this->createMock(ICredentials::class);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->expects($this->once())
			->method('getPassword')
			->willThrowException($this->createMock(PasswordUnavailableException::class));
		$this->provisioningManager->expects($this->never())
			->method('updatePassword');

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}

	public function testBeforeControllerPasswordlessSignin() {
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$configs = [new Provisioning()];
		$this->provisioningManager->expects($this->once())
			->method('getConfigs')
			->willReturn($configs);
		$this->provisioningManager->expects($this->once())
			->method('provisionSingleUser')
			->with($configs, $user);
		$credentials = $this->createMock(ICredentials::class);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->expects($this->once())
			->method('getPassword')
			->willReturn(null);

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}

	public function testBeforeControllerNoConfigAvailable() {
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->provisioningManager->expects($this->any())
			->method('getConfigs')
			->willReturn([]);
		$this->credentialStore->expects($this->never())
			->method('getLoginCredentials');
		$this->provisioningManager->expects($this->never())
			->method('updatePassword');

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}

	public function testBeforeController() {
		$user = $this->createConfiguredMock(IUser::class, [
			'getEmailAddress' => 'bruce.wayne@batman.com'
		]);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$config = new Provisioning();
		$config->setId(1);
		$configs = [$config];
		$this->provisioningManager->expects($this->once())
			->method('getConfigs')
			->willReturn($configs);
		$this->provisioningManager->expects($this->once())
			->method('provisionSingleUser')
			->with($configs, $user);
		$credentials = $this->createMock(ICredentials::class);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->expects($this->once())
			->method('getPassword')
			->willReturn('123456');
		$this->provisioningManager->expects($this->once())
			->method('updatePassword')
			->with(
				$user,
				'123456',
				$configs
			);

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}
}
