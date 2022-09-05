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
use Psr\Log\LoggerInterface;

class ProvisioningMiddlewareTest extends TestCase {
	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IStore|MockObject */
	private $credentialStore;

	/** @var Manager|MockObject */
	private $provisioningManager;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var ProvisioningMiddleware */
	private $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->credentialStore = $this->createMock(IStore::class);
		$this->provisioningManager = $this->createMock(Manager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->middleware = new ProvisioningMiddleware(
			$this->userSession,
			$this->credentialStore,
			$this->provisioningManager,
			$this->logger
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
		$this->provisioningManager->expects($this->never())
			->method('updatePassword');

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
				'123456'
			);

		$this->middleware->beforeController(
			$this->createMock(PageController::class),
			'index'
		);
	}
}
