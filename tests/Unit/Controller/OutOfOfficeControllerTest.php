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
use DateTimeImmutable;
use OCA\Mail\Account;
use OCA\Mail\Controller\OutOfOfficeController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class OutOfOfficeControllerTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private OutOfOfficeController $outOfOfficeController;

	/** @var ContainerInterface|MockObject */
	private $container;

	/** @var IAvailabilityCoordinator|MockObject */
	private $availabilityCoordinator;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(IAvailabilityCoordinator::class)
		 || !interface_exists(IOutOfOfficeData::class)) {
			$this->markTestSkipped('Out-of-office feature is not available');
		}

		$this->container = $this->createMock(ContainerInterface::class);
		$this->availabilityCoordinator = $this->createMock(IAvailabilityCoordinator::class);

		$this->container->expects(self::once())
			->method('get')
			->with(IAvailabilityCoordinator::class)
			->willReturn($this->availabilityCoordinator);

		$this->serviceMock = $this->createServiceMock(OutOfOfficeController::class, [
			'userId' => 'user',
			'container' => $this->container,
		]);
		$this->outOfOfficeController = $this->serviceMock->getService();
	}

	private function createOutOfOfficeData(
		string $id,
		IUser $user,
		int $startDate,
		int $endDate,
		string $subject,
		string $message,
	): ?IOutOfOfficeData {
		if (!interface_exists(IOutOfOfficeData::class)) {
			return null;
		}

		$data = $this->createMock(IOutOfOfficeData::class);
		$data->method('getId')->willReturn($id);
		$data->method('getUser')->willReturn($user);
		$data->method('getStartDate')->willReturn($startDate);
		$data->method('getEndDate')->willReturn($endDate);
		$data->method('getShortMessage')->willReturn($subject);
		$data->method('getMessage')->willReturn($message);
		return $data;
	}


	public function disabledOutOfOfficeDataProvider(): array {
		$user = $this->createMock(IUser::class);
		return [
			[null],
			[$this->createOutOfOfficeData('2', $user, 0, 1, '', '')],
			[$this->createOutOfOfficeData('2', $user, PHP_INT_MAX - 1, PHP_INT_MAX, '', '')],
			[$this->createOutOfOfficeData('2', $user, 0, 1500, 'Subject', 'Message')],
		];
	}

	/**
	 * @dataProvider disabledOutOfOfficeDataProvider
	 */
	public function testFollowSystemWithDisabledOutOfOffice(?IOutOfOfficeData $data): void {
		$user = $this->createMock(IUser::class);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem(false);
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
		$timeFactory = $this->serviceMock->getParameter('timeFactory');
		$timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(1500);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->with($user)
			->willReturn($data);
		$outOfOfficeService = $this->serviceMock->getParameter('outOfOfficeService');
		$outOfOfficeService->expects(self::once())
			->method('disable')
			->with($mailAccount);
		$outOfOfficeService->expects(self::never())
			->method('update');

		$this->outOfOfficeController->followSystem(1);

		self::assertTrue($mailAccount->getOutOfOfficeFollowsSystem());
	}

	public function enabledOutOfOfficeDataProvider(): array {
		$user = $this->createMock(IUser::class);
		return [
			[$this->createOutOfOfficeData('2', $user, 1000, 2000, 'Subject', 'Message')],
			[$this->createOutOfOfficeData('2', $user, 1500, 2000, 'Subject', 'Message')],
		];
	}

	/**
	 * @dataProvider enabledOutOfOfficeDataProvider
	 */
	public function testFollowSystemWithEnabledOutOfOffice(?IOutOfOfficeData $data): void {
		$startDate = $data->getStartDate();
		$endDate = $data->getEndDate();

		$user = $this->createMock(IUser::class);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem(false);
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
		$timeFactory = $this->serviceMock->getParameter('timeFactory');
		$timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(1500);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->with($user)
			->willReturn($data);
		$outOfOfficeService = $this->serviceMock->getParameter('outOfOfficeService');
		$outOfOfficeService->expects(self::never())
			->method('disable');
		$outOfOfficeService->expects(self::once())
			->method('update')
			->with($mailAccount, self::callback(function (OutOfOfficeState $state) use ($endDate, $startDate): bool {
				self::assertTrue($state->isEnabled());
				self::assertEquals(new DateTimeImmutable("@$startDate"), $state->getStart());
				self::assertEquals(new DateTimeImmutable("@$endDate"), $state->getEnd());
				self::assertEquals('Subject', $state->getSubject());
				self::assertEquals('Message', $state->getMessage());
				return true;
			}));

		$this->outOfOfficeController->followSystem(1);

		self::assertTrue($mailAccount->getOutOfOfficeFollowsSystem());
	}
}
