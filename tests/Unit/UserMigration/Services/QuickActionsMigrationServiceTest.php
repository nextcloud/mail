<?php

namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Actions;
use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Model\EnrichedSmimeCertificate;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCA\Mail\UserMigration\Service\QuickActionsMigrationService;
use OCA\Mail\UserMigration\Service\SMIMEMigrationService;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use function OCA\Mail\array_flat_map;

class QuickActionsMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private const FIRST_QUICK_STEP_ID = 1;
	private const SECOND_QUICK_STEP_ID = 2;
	private const QUICK_STEP_ACCOUNT_ID = 5;
	private OutputInterface $output;

	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private QuickActionsService $quickActionsService;
	private ActionStepMapper $actionStepMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->quickActionsService = $this->createMock(QuickActionsService::class);
		$this->actionStepMapper = $this->createMock(ActionStepMapper::class);
	}

	public function testExportsMultipleQuickActions(): void {
		$quickActions = $this->getQuickActions();
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode($quickActions));

		$this->quickActionsService->method('findAll')->with(self::USER_ID)->willReturn($quickActions);
		$service = new QuickActionsMigrationService($this->quickActionsService, $this->actionStepMapper, $this->l);
		$service->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoQuickActions(): void {
		$this->quickActionsService->method('findAll')->with(self::USER_ID)->willReturn([]);
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE, json_encode([]));

		$service = new QuickActionsMigrationService($this->quickActionsService, $this->actionStepMapper, $this->l);
		$service->exportQuickActions($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleQuickActions(): void {
		$quickActions = $this->getQuickActions();
		$this->importSource->expects(self::once())->method('getFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)->willReturn(json_encode($quickActions));

		$accountMapping = [5 => rand(100, 199)];
		$mailboxMapping = [5 => rand(200, 299)];
		$tagMapping = [2 => rand(300, 399)];

		$this->quickActionsService->expects(self::exactly(2))->method('create')->with(self::callback(function ($quickActionName) use ($quickActions): bool {
			return array_any($quickActions, function ($quickAction) use ($quickActionName): bool {
				return $quickAction->getName() === $quickActionName;
			});
			}), $accountMapping[5])->willReturnCallback(function ($name, $accountMapping): Actions {
				$newActions = new Actions();
				$newActions->setId(rand(400, 499));
				$newActions->setName($name);
				$newActions->setAccountId($accountMapping);
				return $newActions;
		});

		$this->quickActionsService->expects(self::exactly(4))->method('createActionStep')->with(self::callback(function ($actionStepName): bool {
			return $this->actionStepNameExists($actionStepName);
		}), self::callback(function ($actionStepOrder): bool {
			return $this->actionStepOrderExists($actionStepOrder);
		}), $this->greaterThanOrEqual(400), $this->logicalOr($this->equalTo($tagMapping[2]), $this->isNull()), $this->logicalOr($this->equalTo($mailboxMapping[5]), $this->isNull()));

		$service = new QuickActionsMigrationService($this->quickActionsService, $this->actionStepMapper, $this->l);
		$service->importQuickActions($this->importSource, $accountMapping, $mailboxMapping, $tagMapping);
	}

	public function testImportNoQuickActions(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(QuickActionsMigrationService::QUICK_ACTIONS_FILE)->willReturn(json_encode([]));
		$this->quickActionsService->expects(self::never())->method('create');
		$this->actionStepMapper->expects(self::never())->method('insert');
		$service = new QuickActionsMigrationService($this->quickActionsService, $this->actionStepMapper, $this->l);
		$service->importQuickActions($this->importSource, [], [], []);
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

		return array_any($quickActions, function ($quickAction) use ($actionStepName): bool {
			return $quickAction->getName() === $actionStepName;
		});
	}

	private function actionStepOrderExists(int $actionStepOrder): bool {
		$quickActions = $this->getActionSteps();

		return array_any($quickActions, function ($quickAction) use ($actionStepOrder): bool {
			return $quickAction->getOrder() === $actionStepOrder;
		});
	}
}
