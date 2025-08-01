<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Db\TextBlock;
use OCA\Mail\Db\TextBlockMapper;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Db\TextBlockShareMapper;
use OCA\Mail\Service\TextBlockService;
use OCP\IGroupManager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextBlockServiceTest extends TestCase {
	/** @var TextBlockMapper|MockObject */
	private $textBlockMapper;

	/** @var TextBlockShareMapper|MockObject */
	private $textBlockShareMapper;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IGroupManager|MockObject */
	private $groupManager;

	/** @var TextBlockService */
	private $textBlockService;

	protected function setUp(): void {
		$this->textBlockMapper = $this->createMock(TextBlockMapper::class);
		$this->textBlockShareMapper = $this->createMock(TextBlockShareMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->textBlockService = new TextBlockService(
			$this->textBlockMapper,
			$this->textBlockShareMapper,
			$this->groupManager,
			$this->userManager
		);
	}

	public function testFindAll(): void {
		$userId = 'bob';
		$textBlocks = [new TextBlock(), new TextBlock()];

		$this->textBlockMapper->expects($this->once())
			->method('findAll')
			->with($userId)
			->willReturn($textBlocks);

		$result = $this->textBlockService->findAll($userId);

		$this->assertSame($textBlocks, $result);
	}

	public function testCreate(): void {
		$userId = 'bob';
		$title = 'Test Text Block';
		$content = '<p>This is content</p>';
		$textBlock = new TextBlock();

		$this->textBlockMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (TextBlock $s) use ($userId, $title, $content) {
				return $s->getOwner() === $userId
					   && $s->getTitle() === $title
					   && $s->getContent() === $content
					   && $s->getPreview() === 'This is content';
			}))
			->willReturn($textBlock);

		$result = $this->textBlockService->create($userId, $title, $content);

		$this->assertSame($textBlock, $result);
	}

	public function testUpdate(): void {
		$textBlockId = 1;
		$userId = 'bob';
		$title = 'Updated Title';
		$content = 'Updated Content';
		$textBlock = new TextBlock();
		$textBlock->setId($textBlockId);
		$textBlock->setOwner($userId);
		$this->textBlockMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (TextBlock $s) use ($title, $content) {
				return $s->getTitle() === $title && $s->getContent() === $content;
			}))
			->willReturn($textBlock);

		$result = $this->textBlockService->update($textBlock, $userId, $title, $content);

		$this->assertSame($textBlock, $result);
	}


	public function testDelete(): void {
		$textBlockId = 1;
		$userId = 'bob';
		$textBlock = new TextBlock();

		$this->textBlockMapper->expects($this->once())
			->method('find')
			->with($textBlockId, $userId)
			->willReturn($textBlock);

		$this->textBlockMapper->expects($this->once())
			->method('delete')
			->with($textBlock);

		$this->textBlockShareMapper->expects($this->once())
			->method('deleteByTextBlockId')
			->with($textBlockId);

		$this->textBlockService->delete($textBlockId, $userId);
	}

	public function testDeleteTextBlockDoesNotExist(): void {
		$textBlockId = 1;
		$userId = 'bob';

		$this->textBlockMapper->expects($this->once())
			->method('find')
			->with($textBlockId, $userId)
			->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Text block does not exist'));

		$this->expectException(\OCP\AppFramework\Db\DoesNotExistException::class);
		$this->expectExceptionMessage('Text block does not exist');

		$this->textBlockService->delete($textBlockId, $userId);
	}

	public function testShare(): void {
		$textBlockId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn($this->createMock(\OCP\IUser::class));

		$this->textBlockShareMapper->expects($this->once())
			->method(constraint: 'shareExists')
			->with($textBlockId, $shareWith)
			->willReturn(false);

		$this->textBlockShareMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (TextBlockShare $s) use ($textBlockId, $shareWith) {
				return $s->getTextBlockId() === $textBlockId
					   && $s->getShareWith() === $shareWith
					   && $s->getType() === TextBlockShare::TYPE_USER;
			}));

		$this->textBlockService->share($textBlockId, $shareWith);
	}

	public function testShareExits(): void {
		$textBlockId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn($this->createMock(\OCP\IUser::class));

		$this->textBlockShareMapper->expects($this->once())
			->method('shareExists')
			->with($textBlockId, $shareWith)
			->willReturn(true);

		$this->expectException(\OCA\Mail\Exception\ShareeAlreadyExistsException::class);

		$this->textBlockService->share($textBlockId, $shareWith);
	}

	public function testShareShareeDoesntExits(): void {
		$textBlockId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn(null);

		$this->expectException(\OCA\Mail\Exception\UserNotFoundException::class);
		$this->expectExceptionMessage('Sharee does not exist');

		$this->textBlockService->share($textBlockId, $shareWith);
	}

	public function testShareWithGroupGroupDoesNotExist(): void {
		$textBlockId = 1;
		$groupId = 'nonexistent-group';

		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(false);

		$this->expectException(\OCA\Mail\Exception\UserNotFoundException::class);
		$this->expectExceptionMessage('Group does not exist');

		$this->textBlockService->shareWithGroup($textBlockId, $groupId);
	}

	public function testShareWithGroupAlreadyExists(): void {
		$textBlockId = 1;
		$groupId = 'existing-group';

		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);

		$this->textBlockShareMapper->expects($this->once())
			->method('shareExists')
			->with($textBlockId, $groupId)
			->willReturn(true);

		$this->expectException(\OCA\Mail\Exception\ShareeAlreadyExistsException::class);

		$this->textBlockService->shareWithGroup($textBlockId, $groupId);
	}

	public function testShareWithGroup(): void {
		$textBlockId = 1;
		$groupId = 'valid-group';

		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);

		$this->textBlockShareMapper->expects($this->once())
			->method('shareExists')
			->with($textBlockId, $groupId)
			->willReturn(false);

		$this->textBlockShareMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (TextBlockShare $share) use ($textBlockId, $groupId) {
				return $share->getTextBlockId() === $textBlockId
					   && $share->getShareWith() === $groupId
					   && $share->getType() === TextBlockShare::TYPE_GROUP;
			}));

		$this->textBlockService->shareWithGroup($textBlockId, $groupId);
	}

	public function testUnshare(): void {
		$textBlockId = 1;
		$shareWith = 'alice';
		$textBlockShare = new TextBlockShare();

		$this->textBlockShareMapper->expects($this->once())
			->method('find')
			->with($textBlockId, $shareWith)
			->willReturn($textBlockShare);

		$this->textBlockShareMapper->expects($this->once())
			->method('delete')
			->with($textBlockShare);

		$this->textBlockService->unshare($textBlockId, $shareWith);
	}

	public function testFindAllSharedWithMe(): void {
		$userId = 'alice';
		$user = $this->createMock(\OCP\IUser::class);
		$shares = [new TextBlockShare(), new TextBlockShare()];

		$this->userManager->expects($this->once())
			->method('get')
			->with($userId)
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn([]);

		$this->textBlockMapper->expects($this->once())
			->method('findSharedWithMe')
			->with($userId, [])
			->willReturn($shares);

		$result = $this->textBlockService->findAllSharedWithMe($userId);

		$this->assertSame($shares, $result);
	}

	public function testFindAllSharedWithMeNoUser(): void {
		$userId = 'alice';
		$shares = [new TextBlockShare(), new TextBlockShare()];

		$this->userManager->expects($this->once())
			->method('get')
			->with($userId)
			->willReturn(null);

		$this->textBlockMapper->expects($this->never())
			->method('findSharedWithMe');

		$this->expectException(\OCA\Mail\Exception\UserNotFoundException::class);
		$this->textBlockService->findAllSharedWithMe($userId);

	}

	public function testFind(): void {
		$textBlockId = 1;
		$userId = 'alice';
		$textBlock = new TextBlock();

		$this->textBlockMapper->expects($this->once())
			->method('find')
			->with($textBlockId, $userId)
			->willReturn($textBlock);

		$result = $this->textBlockService->find($textBlockId, $userId);

		$this->assertSame($textBlock, $result);
	}

	public function testGetShares(): void {
		$textBlockId = 1;
		$shares = [new TextBlockShare(), new TextBlockShare()];

		$this->textBlockShareMapper->expects($this->once())
			->method('findTextBlockShares')
			->with($textBlockId)
			->willReturn($shares);

		$result = $this->textBlockService->getShares($textBlockId);

		$this->assertSame($shares, $result);
	}


}
