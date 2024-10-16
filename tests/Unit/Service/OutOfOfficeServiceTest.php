<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\User\IOutOfOfficeData;

class OutOfOfficeServiceTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private OutOfOfficeService $outOfOfficeService;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(OutOfOfficeService::class);
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
		$availabilityCoordinator = $this->serviceMock->getParameter('availabilityCoordinator');
		$availabilityCoordinator->expects(self::once())
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
		$allowedRecipientsService = $this->serviceMock->getParameter('allowedRecipientsService');
		$allowedRecipientsService->expects(self::once())
			->method('get')
			->with($mailAccount)
			->willReturn(['email@domain.com']);
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
		$availabilityCoordinator = $this->serviceMock->getParameter('availabilityCoordinator');
		$availabilityCoordinator->expects(self::once())
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
		$allowedRecipientsService = $this->serviceMock->getParameter('allowedRecipientsService');
		$allowedRecipientsService->expects(self::once())
			->method('get')
			->with($mailAccount)
			->willReturn(['email@domain.com']);
		$sieveService->expects(self::once())
			->method('updateActiveScript')
			->with('user', 1, '# new sieve script');

		$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
		self::assertNull($state);
		self::assertFalse($oldState->isEnabled());
	}
}
