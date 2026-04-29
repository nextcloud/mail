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
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode($quickActions));

		$this->serviceMock->getParameter('quickActionsService')->method('findAll')->with(self::USER_ID)->willReturn($quickActions);
		$this->migrationService->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoQuickActions(): void {
		$this->serviceMock->getParameter('quickActionsService')->method('findAll')->with(self::USER_ID)->willReturn([]);
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode([]));

		$this->migrationService->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleQuickActions(): void {
		$quickActions = $this->getQuickActions();
		$this->importSource->expects(self::once())->method('getFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)->willReturn(json_encode($quickActions));

		$accountMapping['accounts'] = [5 => rand(100, 199)];
		$accountMapping['mailboxes'] = [5 => rand(200, 299)];
		$tagMapping = [2 => rand(300, 399)];

		$this->serviceMock->getParameter('quickActionsService')->expects(self::exactly(2))->method('create')->with(self::callback(function ($quickActionName) use ($quickActions): bool {
			return !empty(array_filter($quickActions, function ($quickAction) use ($quickActionName): bool {
				return $quickAction->getName() === $quickActionName;
			}));
		}), $accountMapping['accounts'][5])->willReturnCallback(function ($name, $mappedAccount): Actions {
			$newActions = new Actions();
			$newActions->setId(rand(400, 499));
			$newActions->setName($name);
			$newActions->setAccountId($mappedAccount);
			return $newActions;
		});

		$this->serviceMock->getParameter('quickActionsService')->expects(self::exactly(4))->method('createActionStep')->with(self::callback(function ($actionStepName): bool {
			return $this->actionStepNameExists($actionStepName);
		}), self::callback(function ($actionStepOrder): bool {
			return $this->actionStepOrderExists($actionStepOrder);
		}), $this->greaterThanOrEqual(400), $this->logicalOr($this->equalTo($tagMapping[2]), $this->isNull()), $this->logicalOr($this->equalTo($accountMapping['mailboxes'][5]), $this->isNull()));

		$this->migrationService->importQuickActions($this->user, $this->importSource, $this->output, $accountMapping, $tagMapping);
	}

	public function testImportNoQuickActions(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)->willReturn(json_encode([]));
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

	private function getActionSteps(): array {
		$actionSteps = [$this->getActionStepsForFirstQuickAction(), $this->getActionStepsForSecondQuickAction()];
		$flattenedActionSteps = [];

		array_walk_recursive($actionSteps, function ($item) use (&$flattenedActionSteps) {
			$flattenedActionSteps[] = $item;
		});

		return $flattenedActionSteps;
	}

	private function actionStepNameExists(string $actionStepName): bool {
		$quickActions = $this->getActionSteps();

		return !empty(array_filter($quickActions, function ($quickAction) use ($actionStepName): bool {
			return $quickAction->getName() === $actionStepName;
		}));
	}

	private function actionStepOrderExists(int $actionStepOrder): bool {
		$quickActions = $this->getActionSteps();

		return !empty(array_filter($quickActions, function ($quickAction) use ($actionStepOrder): bool {
			return $quickAction->getOrder() === $actionStepOrder;
		}));
	}
}
