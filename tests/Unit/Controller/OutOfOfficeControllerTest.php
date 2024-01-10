<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\OutOfOfficeController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCP\AppFramework\Http;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;

class OutOfOfficeControllerTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private OutOfOfficeController $outOfOfficeController;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(OutOfOfficeController::class, [
			'userId' => 'user',
		]);
		$this->outOfOfficeController = $this->serviceMock->getService();
	}

	public function followSystemDataProvider(): array {
		return [
			[false],
			[true],
		];
	}

	/**
	 * @dataProvider followSystemDataProvider
	 */
	public function testFollowSystem(bool $followSystem): void {
		if (!interface_exists(IAvailabilityCoordinator::class)) {
			$this->markTestSkipped('Out-of-office feature is not available');
			return;
		}

		$container = $this->serviceMock->getParameter('container');
		$container->expects(self::once())
			->method('has')
			->with(IAvailabilityCoordinator::class)
			->willReturn(true);

		$user = $this->createMock(IUser::class);
		$state = $this->createMock(OutOfOfficeState::class);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem($followSystem);
		$account = new Account($mailAccount);

		$userSession = $this->serviceMock->getParameter('userSession');
		$userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($account);
		$outOfOfficeService = $this->serviceMock->getParameter('outOfOfficeService');
		$outOfOfficeService->expects(self::once())
			->method('updateFromSystem')
			->with($mailAccount, $user)
			->willReturn($state);

		$response = $this->outOfOfficeController->followSystem(1);
		self::assertEquals($response, JsonResponse::success($state));

		self::assertTrue($mailAccount->getOutOfOfficeFollowsSystem());
	}

	public function testFollowSystemWithoutOutOfOfficeFeature(): void {
		$container = $this->serviceMock->getParameter('container');
		$container->expects(self::once())
			->method('has')
			->with(IAvailabilityCoordinator::class)
			->willReturn(false);

		$response = $this->outOfOfficeController->followSystem(1);
		self::assertEquals($response, JsonResponse::fail([], Http::STATUS_NOT_IMPLEMENTED));
	}
}
