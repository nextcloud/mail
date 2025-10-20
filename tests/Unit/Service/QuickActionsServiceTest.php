<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Actions;
use OCA\Mail\Db\ActionsMapper;
use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\QuickActionsService;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;

class QuickActionsServiceTest extends TestCase {

	/** @var ActionsMapper|MockObject */
	private $actionsMapper;

	/** @var ActionStepMapper|MockObject */
	private $actionStepMapper;

	private QuickActionsService $quickActionsService;

	protected function setUp(): void {
		parent::setUp();

		$this->actionsMapper = $this->createMock(ActionsMapper::class);
		$this->actionStepMapper = $this->createMock(ActionStepMapper::class);

		$this->quickActionsService = new QuickActionsService(
			$this->actionsMapper,
			$this->actionStepMapper,
		);
	}

	public function testFindAll(): void {
		$userId = 'user123';
		$action = new Actions();
		$action->setId(1);
		$action->setName('Test Action');
		$action->setAccountId(1);
		$actionStep = new ActionStep();
		$actionStep->setName('markAsUnread');
		$actionStep->setOrder(1);
		$actionStep->setActionId(1);


		$this->actionsMapper->expects($this->once())
			->method('findAll')
			->with($userId)
			->willReturn([$action]);

		$this->actionStepMapper->expects($this->once())
			->method('findStepsByActionIds')
			->with([$action->getId()], $userId)
			->willReturn([$actionStep]);

		$result = $this->quickActionsService->findAll($userId);

		$action->setActionSteps([$actionStep]);

		$this->assertEquals($result, [$action]);
		$this->assertIsArray($result);
		$this->assertCount(1, $result);
	}


	public function testFind(): void {
		$actionId = 1;
		$userId = 'user123';
		$action = new Actions();
		$action->setId($actionId);
		$action->setName('Test Action');
		$action->setAccountId(1);

		$this->actionsMapper->expects($this->once())
			->method('find')
			->with($actionId, $userId)
			->willReturn($action);

		$result = $this->quickActionsService->find($actionId, $userId);

		$this->assertInstanceOf(Actions::class, $result);
		$this->assertEquals($actionId, $result->getId());
		$this->assertEquals('Test Action', $result->getName());
		$this->assertEquals(1, $result->getAccountId());
	}

	public function testFindThrowsDoesNotExistException(): void {
		$actionId = 1;
		$userId = 'user123';

		$this->actionsMapper->expects($this->once())
			->method('find')
			->with($actionId, $userId)
			->willThrowException(new DoesNotExistException('Not found'));

		$this->expectException(DoesNotExistException::class);

		$this->quickActionsService->find($actionId, $userId);
	}

	public function testCreate(): void {
		$name = 'New Action';
		$accountId = 1;
		$action = new Actions();
		$action->setName($name);
		$action->setAccountId($accountId);

		$this->actionsMapper->expects($this->once())
			->method('insert')
			->with($action)
			->willReturn($action);

		$result = $this->quickActionsService->create($name, $accountId);

		$this->assertInstanceOf(Actions::class, $result);
		$this->assertEquals($name, $result->getName());
		$this->assertEquals($accountId, $result->getAccountId());
	}

	public function testUpdate(): void {
		$action = new Actions();
		$action->setId(1);
		$action->setName('Old Action');
		$action->setAccountId(1);

		$newName = 'Updated Action';
		$updatedAction = new Actions();
		$updatedAction->setId(1);
		$updatedAction->setName($newName);
		$updatedAction->setAccountId(1);

		$this->actionsMapper->expects($this->once())
			->method('update')
			->with($updatedAction)
			->willReturn($updatedAction);

		$result = $this->quickActionsService->update($action, $newName);

		$this->assertInstanceOf(Actions::class, $result);
		$this->assertEquals(1, $result->getId());
		$this->assertEquals($newName, $result->getName());
		$this->assertEquals(1, $result->getAccountId());
	}

	public function testDelete(): void {
		$actionId = 1;
		$userId = 'user123';
		$action = new Actions();
		$action->setId($actionId);
		$action->setName('Action to Delete');
		$action->setAccountId(1);

		$this->actionsMapper->expects($this->once())
			->method('find')
			->with($actionId, $userId)
			->willReturn($action);

		$this->actionsMapper->expects($this->once())
			->method('delete')
			->with($action);

		$this->quickActionsService->delete($actionId, $userId);
	}

	public function testFindActionStep(): void {
		$actionId = 1;
		$userId = 'user123';
		$actionStep = new ActionStep();
		$this->actionStepMapper->expects($this->once())
			->method('find')
			->with($actionId, $userId)
			->willReturn($actionStep);
		$result = $this->quickActionsService->findActionStep($actionId, $userId);
		$this->assertInstanceOf(ActionStep::class, $result);
		$this->assertEquals($actionStep, $result);
	}

	public function testCreateActionStep(): void {
		$name = 'markAsSpam';
		$order = 1;
		$actionId = 1;
		$actionStep = new ActionStep();
		$actionStep->setName($name);
		$actionStep->setOrder($order);
		$actionStep->setActionId($actionId);

		$this->actionStepMapper->expects($this->once())
			->method('insert')
			->with($actionStep)
			->willReturn($actionStep);

		$result = $this->quickActionsService->createActionStep($name, $order, $actionId);

		$this->assertInstanceOf(ActionStep::class, $result);
		$this->assertEquals($name, $result->getName());
		$this->assertEquals($order, $result->getOrder());
		$this->assertEquals($actionId, $result->getActionId());
		$this->assertNull($result->getTagId());
		$this->assertNull($result->getMailboxId());
	}

	public function testUpdateActionStep(): void {
		$actionStep = new ActionStep();
		$actionStep->setName('oldName');
		$actionStep->setOrder(1);
		$actionStep->setActionId(1);

		$newName = 'newName';
		$newOrder = 2;
		$newTagId = 3;
		$newMailboxId = 4;

		$updatedActionStep = new ActionStep();
		$updatedActionStep->setName($newName);
		$updatedActionStep->setOrder($newOrder);
		$updatedActionStep->setActionId(1);
		$updatedActionStep->setTagId($newTagId);
		$updatedActionStep->setMailboxId($newMailboxId);

		$this->actionStepMapper->expects($this->once())
			->method('update')
			->with($updatedActionStep)
			->willReturn($updatedActionStep);

		$result = $this->quickActionsService->updateActionStep($actionStep, $newName, $newOrder, $newTagId, $newMailboxId);

		$this->assertInstanceOf(ActionStep::class, $result);
		$this->assertEquals($newName, $result->getName());
		$this->assertEquals($newOrder, $result->getOrder());
		$this->assertEquals(1, $result->getActionId());
		$this->assertEquals($newTagId, $result->getTagId());
		$this->assertEquals($newMailboxId, $result->getMailboxId());
	}

	public function testDeleteActionStep(): void {
		$actionStep = new ActionStep();
		$this->actionStepMapper->expects($this->once())
			->method('find')
			->with(1, 'user123')
			->willReturn($actionStep);
		$this->actionStepMapper->expects($this->once())
			->method('delete')
			->with($actionStep);
		$this->quickActionsService->deleteActionStep(1, 'user123');
	}

	public function testCreateActionStepInvalidName(): void {
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Invalid action step');

		$this->quickActionsService->createActionStep('invalidName', 1, 1);
	}

	public function testCreateActionStepOrderTooHigh(): void {
		$this->actionStepMapper->expects($this->once())
			->method('findHighestOrderStep')
			->with(1)
			->willReturn(null);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Invalid action step order');

		$this->quickActionsService->createActionStep('markAsSpam', 5, 1);
	}

	public function testCreateActionStepAfterDeleteThread(): void {
		$highestOrderStep = new ActionStep();
		$highestOrderStep->setName('deleteThread');

		$this->actionStepMapper->expects($this->once())
			->method('findHighestOrderStep')
			->with(1)
			->willReturn($highestOrderStep);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Cant perform actions after deleteThread');

		$this->quickActionsService->createActionStep('applyTag', 2, 1);
	}

	public function testCreateActionStepAfterMoveThread(): void {
		$highestOrderStep = new ActionStep();
		$highestOrderStep->setName('moveThread');

		$this->actionStepMapper->expects($this->once())
			->method('findHighestOrderStep')
			->with(1)
			->willReturn($highestOrderStep);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Cant perform actions after moveThread');

		$this->quickActionsService->createActionStep('applyTag', 2, 1);
	}

	public function testCreateActionStepAfterMarkAsSpam(): void {
		$highestOrderStep = new ActionStep();
		$highestOrderStep->setName('markAsSpam');

		$this->actionStepMapper->expects($this->once())
			->method('findHighestOrderStep')
			->with(1)
			->willReturn($highestOrderStep);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Cant perform actions after markAsSpam');

		$this->quickActionsService->createActionStep('applyTag', 2, 1);
	}

	public function testCreateActionStepInvalidOrder(): void {
		$highestOrderStep = new ActionStep();
		$highestOrderStep->setOrder(2);

		$this->actionStepMapper->expects($this->once())
			->method('findHighestOrderStep')
			->with(1)
			->willReturn($highestOrderStep);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Invalid action step order');

		$this->quickActionsService->createActionStep('applyTag', 4, 1);
	}

	public function testCreateActionApplyTagWithoutTagId(): void {
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('TagId is required for applyTag action step');

		$this->quickActionsService->createActionStep('applyTag', 1, 1);
	}
	public function testCreateActionMoveThreadWithoutMailboxId(): void {
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('MailboxId is required for moveThread action step');

		$this->quickActionsService->createActionStep('moveThread', 1, 1);
	}


}
