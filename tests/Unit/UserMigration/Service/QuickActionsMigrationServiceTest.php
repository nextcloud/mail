<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Actions;
use OCA\Mail\Db\ActionStep;
use OCA\Mail\UserMigration\Service\QuickActionsMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class QuickActionsMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private const FIRST_QUICK_STEP_ID = 1;
	private const SECOND_QUICK_STEP_ID = 2;
	private const QUICK_STEP_ACCOUNT_ID = 5;

	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private QuickActionsMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(QuickActionsMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();
	}

	public function testExportsMultipleQuickActions(): void {
		$quickActions = $this->getQuickActions();
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode($quickActions));

		$this->serviceMock->getParameter('quickActionsService')
			->method('findAll')
			->with(self::USER_ID)
			->willReturn($quickActions);
		$this->migrationService->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoQuickActions(): void {
		$this->serviceMock->getParameter('quickActionsService')->method('findAll')->with(self::USER_ID)->willReturn([]);
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode([]));

		$this->migrationService->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleQuickActions(): void {
		$quickActions = $this->getQuickActions();
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)
			->willReturn(json_encode($quickActions));

		$accountMapping = [5 => rand(100, 199)];
		$mailboxMapping = [5 => rand(200, 299)];
		$tagMapping = [2 => rand(300, 399)];

		$createdIds = [];
		$createCallCount = 0;
		$expectedActions = $this->getQuickActions();
		$this->serviceMock
			->getParameter('quickActionsService')->expects(self::exactly(2))->method('create')
			->willReturnCallback(function (string $name, int $accountId) use (
				&$createCallCount,
				&$createdIds,
				$expectedActions,
				$accountMapping
			): Actions {
				$expected = $expectedActions[$createCallCount];
				self::assertSame($expected->getName(), $name);
				self::assertSame($accountMapping[$expected->getAccountId()], $accountId);
				$newActions = new Actions();
				$newActions->setId(rand(400, 499));
				$newActions->setName($name);
				$newActions->setAccountId($accountId);
				$createdIds[] = $newActions->getId();
				$createCallCount++;
				return $newActions;
			});

		$expectedSteps = [
			['name' => 'markAsRead', 'order' => 1, 'actionIndex' => 0, 'tagId' => null, 'mailboxId' => null],
			['name' => 'moveThread', 'order' => 2, 'actionIndex' => 0, 'tagId' => null, 'mailboxId' => $mailboxMapping[5]],
			['name' => 'markAsImportant', 'order' => 1, 'actionIndex' => 1, 'tagId' => null, 'mailboxId' => null],
			['name' => 'applyTag', 'order' => 2, 'actionIndex' => 1, 'tagId' => $tagMapping[2], 'mailboxId' => null],
		];
		$createStepCallCount = 0;
		$this->serviceMock
			->getParameter('quickActionsService')->expects(self::exactly(4))->method('createActionStep')
			->willReturnCallback(function (string $name, int $order, int $actionId, ?int $tagId, ?int $mailboxId) use (
				&
				$createStepCallCount,
				&$createdIds,
				$expectedSteps
			): ActionStep {
				$expected = $expectedSteps[$createStepCallCount];
				self::assertSame($expected['name'], $name);
				self::assertSame($expected['order'], $order);
				self::assertSame($createdIds[$expected['actionIndex']], $actionId);
				self::assertSame($expected['tagId'], $tagId);
				self::assertSame($expected['mailboxId'], $mailboxId);
				$newStep = new ActionStep();
				$newStep->setId(rand(500, 599));
				$createStepCallCount++;
				return $newStep;
			});

		$this->migrationService->importQuickActions($this->user, $this->importSource, $this->output, $accountMapping,
			$mailboxMapping, $tagMapping);
	}

	public function testImportNoQuickActions(): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)
			->willReturn(json_encode([]));
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('create');
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('createActionStep');
		$this->migrationService->importQuickActions($this->user, $this->importSource, $this->output, [], [], []);
	}

	public static function provideFileContentsWithNoQuickActionsImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'JSON object instead of list' => [json_encode(['unexpected' => 'object'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoQuickActionsImported
	 */
	public function testImportEmptyOrInvalidQuickActions(string $fileContents): void {
		$this->importSource
			->expects(self::once())->method('getFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)
			->willReturn($fileContents);
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('create');
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('createActionStep');
		$this->migrationService->importQuickActions($this->user, $this->importSource, $this->output, [], [], []);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource
			->expects(self::once())
			->method('getFileContents')
			->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)
			->willThrowException(new UserMigrationException());
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('create');
		$this->serviceMock->getParameter('quickActionsService')->expects(self::never())->method('createActionStep');
		$this->migrationService->importQuickActions($this->user, $this->importSource, $this->output, [], [], []);
	}

	private function getFirstQuickAction(): Actions {
		$firstAction = new Actions();

		$firstAction->setId(self::FIRST_QUICK_STEP_ID);
		$firstAction->setAccountId(self::QUICK_STEP_ACCOUNT_ID);
		$firstAction->setName('First quick action');

		$actionSteps = $this->getActionStepsForFirstQuickAction();
		$firstAction->setActionSteps($actionSteps);

		return $firstAction;
	}

	private function getActionStepsForFirstQuickAction(): array {
		$firstStep = new ActionStep();
		$firstStep->setId(1);
		$firstStep->setActionId(self::QUICK_STEP_ACCOUNT_ID);
		$firstStep->setName('markAsRead');
		$firstStep->setOrder(1);

		$secondStep = new ActionStep();
		$secondStep->setId(2);
		$secondStep->setActionId(self::QUICK_STEP_ACCOUNT_ID);
		$secondStep->setMailboxId(5);
		$secondStep->setName('moveThread');
		$secondStep->setOrder(2);

		return [$firstStep, $secondStep];
	}

	private function getSecondQuickAction(): Actions {
		$secondAction = new Actions();

		$secondAction->setId(self::SECOND_QUICK_STEP_ID);
		$secondAction->setAccountId(self::QUICK_STEP_ACCOUNT_ID);
		$secondAction->setName('Second quick action');

		$actionSteps = $this->getActionStepsForSecondQuickAction();
		$secondAction->setActionSteps($actionSteps);

		return $secondAction;
	}

	private function getActionStepsForSecondQuickAction(): array {
		$firstStep = new ActionStep();
		$firstStep->setId(3);
		$firstStep->setActionId(self::SECOND_QUICK_STEP_ID);
		$firstStep->setName('markAsImportant');
		$firstStep->setOrder(1);

		$secondStep = new ActionStep();
		$secondStep->setId(4);
		$secondStep->setActionId(self::SECOND_QUICK_STEP_ID);
		$secondStep->setTagId(2);
		$secondStep->setName('applyTag');
		$secondStep->setOrder(2);

		return [$firstStep, $secondStep];
	}

	private function getQuickActions(): array {
		return [$this->getFirstQuickAction(), $this->getSecondQuickAction()];
	}
}
