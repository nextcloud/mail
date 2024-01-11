<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace Unit\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParserResult;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCA\Mail\Service\OutOfOfficeService;
use OCA\Mail\Sieve\NamedSieveScript;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class OutOfOfficeServiceTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private OutOfOfficeService $outOfOfficeService;

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

		$this->serviceMock = $this->createServiceMock(OutOfOfficeService::class, [
			'container' => $this->container,
		]);
		$this->outOfOfficeService = $this->serviceMock->getService();
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

	public function testFollowSystemWithNotFollowingAccount(): void {
		$user = $this->createMock(IUser::class);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem(false);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The mail account does not follow system out-of-office settings');
		$this->outOfOfficeService->updateFromSystem($mailAccount, $user);
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
	public function testUpdateFromSystemWithEnabledOutOfOffice(?IOutOfOfficeData $data): void {
		$startDate = $data->getStartDate();
		$endDate = $data->getEndDate();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem(true);
		$mailAccount->setUserId('user');
		$mailAccount->setEmail('email@domain.com');

		$timeFactory = $this->serviceMock->getParameter('timeFactory');
		$timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(1500);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->with($user)
			->willReturn($data);
		$sieveService = $this->serviceMock->getParameter('sieveService');
		$sieveService->expects(self::once())
			->method('getActiveScript')
			->with('user', 1)
			->willReturn(new NamedSieveScript('nextcloud', '# sieve script'));
		$outOfOfficeParser = $this->serviceMock->getParameter('outOfOfficeParser');
		$outOfOfficeParser->expects(self::once())
			->method('parseOutOfOfficeState')
			->with('# sieve script')
			->willReturn(new OutOfOfficeParserResult(
				null,
				'# sieve script',
				'# sieve script',
			));
		$outOfOfficeParser->expects(self::once())
			->method('buildSieveScript')
			->with(
				new OutOfOfficeState(
					true,
					new DateTimeImmutable("@$startDate"),
					new DateTimeImmutable("@$endDate"),
					'Re: ${subject}',
					$data->getMessage(),
				),
				'# sieve script',
				['email@domain.com'],
			)
			->willReturn('# new sieve script');
		$aliasesService = $this->serviceMock->getParameter('aliasesService');
		$aliasesService->expects(self::once())
			->method('findAll')
			->with(1, 'user')
			->willReturn([]);
		$sieveService->expects(self::once())
			->method('updateActiveScript')
			->with('user', 1, '# new sieve script');

		$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
		self::assertTrue($state->isEnabled());
		self::assertEquals(new DateTimeImmutable("@$startDate"), $state->getStart());
		self::assertEquals(new DateTimeImmutable("@$endDate"), $state->getEnd());
		self::assertEquals('Re: ${subject}', $state->getSubject());
		self::assertEquals('Message', $state->getMessage());
	}

	public function disabledOutOfOfficeDataProvider(): array {
		$user = $this->createMock(IUser::class);
		return [
			[$this->createOutOfOfficeData('2', $user, 1000, 1250, 'Subject', 'Message')],
			[$this->createOutOfOfficeData('2', $user, 1750, 2000, 'Subject', 'Message')],
		];
	}

	/**
	 * @dataProvider disabledOutOfOfficeDataProvider
	 */
	public function testUpdateFromSystemWithDisabledOutOfOffice(?IOutOfOfficeData $data): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setOutOfOfficeFollowsSystem(true);
		$mailAccount->setUserId('user');
		$mailAccount->setEmail('email@domain.com');

		$oldState = new OutOfOfficeState(true, null, null, 'Old subject', 'Old message');

		$timeFactory = $this->serviceMock->getParameter('timeFactory');
		$timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(1500);
		$this->availabilityCoordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->with($user)
			->willReturn($data);
		$sieveService = $this->serviceMock->getParameter('sieveService');
		$sieveService->expects(self::exactly(2))
			->method('getActiveScript')
			->with('user', 1)
			->willReturn(new NamedSieveScript('nextcloud', '# sieve script'));
		$outOfOfficeParser = $this->serviceMock->getParameter('outOfOfficeParser');
		$outOfOfficeParser->expects(self::exactly(2))
			->method('parseOutOfOfficeState')
			->with('# sieve script')
			->willReturn(new OutOfOfficeParserResult(
				$oldState,
				'# sieve script',
				'# sieve script',
			));
		$outOfOfficeParser->expects(self::once())
			->method('buildSieveScript')
			->with(
				new OutOfOfficeState(
					false,
					$oldState->getStart(),
					$oldState->getEnd(),
					$oldState->getSubject(),
					$oldState->getMessage(),
					$oldState->getVersion(),
				),
				'# sieve script',
				['email@domain.com'],
			)
			->willReturn('# new sieve script');
		$aliasesService = $this->serviceMock->getParameter('aliasesService');
		$aliasesService->expects(self::once())
			->method('findAll')
			->with(1, 'user')
			->willReturn([]);
		$sieveService->expects(self::once())
			->method('updateActiveScript')
			->with('user', 1, '# new sieve script');

		$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
		self::assertNull($state);
		self::assertFalse($oldState->isEnabled());
	}
}
