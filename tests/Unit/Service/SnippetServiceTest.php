<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Db\Snippet;
use OCA\Mail\Db\SnippetMapper;
use OCA\Mail\Db\SnippetShare;
use OCA\Mail\Db\SnippetShareMapper;
use OCA\Mail\Service\SnippetService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\NotPermittedException;
use OCP\IGroupManager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SnippetServiceTest extends TestCase {
	/** @var SnippetMapper|MockObject */
	private $snippetMapper;

	/** @var SnippetShareMapper|MockObject */
	private $snippetShareMapper;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IGroupManager|MockObject */
	private $groupManager;

	/** @var SnippetService */
	private $snippetService;

	protected function setUp(): void {
		$this->snippetMapper = $this->createMock(SnippetMapper::class);
		$this->snippetShareMapper = $this->createMock(SnippetShareMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->snippetService = new SnippetService(
			$this->snippetMapper,
			$this->snippetShareMapper,
			$this->groupManager,
			$this->userManager
		);
	}

	public function testFindAll(): void {
		$userId = 'bob';
		$snippets = [new Snippet(), new Snippet()];

		$this->snippetMapper->expects($this->once())
			->method('findAll')
			->with($userId)
			->willReturn($snippets);

		$result = $this->snippetService->findAll($userId);

		$this->assertSame($snippets, $result);
	}

	public function testCreate(): void {
		$userId = 'bob';
		$title = 'Test Snippet';
		$content = '<p>This is content</p>';
		$snippet = new Snippet();

		$this->snippetMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (Snippet $s) use ($userId, $title, $content) {
				return $s->getOwner() === $userId &&
					   $s->getTitle() === $title &&
					   $s->getContent() === $content &&
					   $s->getPreview() === 'This is content';
			}))
			->willReturn($snippet);

		$result = $this->snippetService->create($userId, $title, $content);

		$this->assertSame($snippet, $result);
	}

	public function testUpdate(): void {
		$snippetId = 1;
		$userId = 'bob';
		$title = 'Updated Title';
		$content = 'Updated Content';
		$snippet = new Snippet();
		$snippet->setId($snippetId);
		$snippet->setOwner($userId);

		$this->snippetMapper->expects($this->once())
			->method('find')
			->with($snippetId, $userId)
			->willReturn($snippet);

		$this->snippetMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (Snippet $s) use ($title, $content) {
				return $s->getTitle() === $title && $s->getContent() === $content;
			}))
			->willReturn($snippet);

		$result = $this->snippetService->update($snippetId, $userId, $title, $content);

		$this->assertSame($snippet, $result);
	}

	public function testDelete(): void {
		$snippetId = 1;
		$userId = 'bob';
		$snippet = new Snippet();

		$this->snippetMapper->expects($this->once())
			->method('find')
			->with($snippetId, $userId)
			->willReturn($snippet);

		$this->snippetMapper->expects($this->once())
			->method('delete')
			->with($snippet);

		$this->snippetShareMapper->expects($this->once())
			->method('deleteBySnippetId')
			->with($snippetId);

		$this->snippetService->delete($snippetId, $userId);
	}

	public function testShare(): void {
		$snippetId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn($this->createMock(\OCP\IUser::class));

		$this->snippetShareMapper->expects($this->once())
			->method(constraint: 'shareExists')
			->with($snippetId, $shareWith)
			->willReturn(false);

		$this->snippetShareMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (SnippetShare $s) use ($snippetId, $shareWith) {
				return $s->getSnippetId() === $snippetId &&
					   $s->getShareWith() === $shareWith &&
					   $s->getType() === SnippetShare::TYPE_USER;
			}));

		$this->snippetService->share($snippetId, $shareWith);
	}

	public function testShareExits(): void {
		$snippetId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn($this->createMock(\OCP\IUser::class));

		$this->snippetShareMapper->expects($this->once())
			->method('shareExists')
			->with($snippetId, $shareWith)
			->willReturn(true);

		$this->expectException(NotPermittedException::class);
		$this->expectExceptionMessage('Share already exists');

		$this->snippetService->share($snippetId, $shareWith);
	}

	public function testShareShareeDoesntExits(): void {
		$snippetId = 1;
		$shareWith = 'alice';

		$this->userManager->expects($this->once())
			->method('get')
			->with($shareWith)
			->willReturn(null);

		$this->expectException(DoesNotExistException::class);
		$this->expectExceptionMessage('Sharee does not exist');

		$this->snippetService->share($snippetId, $shareWith);
	}

	public function testShareWithGroupGroupDoesNotExist(): void {
		$snippetId = 1;
		$groupId = 'nonexistent-group';
	
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(false);
	
		$this->expectException(DoesNotExistException::class);
		$this->expectExceptionMessage('Group does not exist');
	
		$this->snippetService->shareWithGroup($snippetId, $groupId);
	}
	
	public function testShareWithGroupAlreadyExists(): void {
		$snippetId = 1;
		$groupId = 'existing-group';
	
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);
	
		$this->snippetShareMapper->expects($this->once())
			->method('shareExists')
			->with($snippetId, $groupId)
			->willReturn(true);
	
		$this->expectException(NotPermittedException::class);
		$this->expectExceptionMessage('Share already exists');
	
		$this->snippetService->shareWithGroup($snippetId, $groupId);
	}
	
	public function testShareWithGroup(): void {
		$snippetId = 1;
		$groupId = 'valid-group';
	
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);
	
		$this->snippetShareMapper->expects($this->once())
			->method('shareExists')
			->with($snippetId, $groupId)
			->willReturn(false);
	
		$this->snippetShareMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function (SnippetShare $share) use ($snippetId, $groupId) {
				return $share->getSnippetId() === $snippetId &&
					   $share->getShareWith() === $groupId &&
					   $share->getType() === SnippetShare::TYPE_GROUP;
			}));
	
		$this->snippetService->shareWithGroup($snippetId, $groupId);
	}

	public function testUnshare(): void {
		$snippetId = 1;
		$shareWith = 'alice';
		$snippetShare = new SnippetShare();

		$this->snippetShareMapper->expects($this->once())
			->method('find')
			->with($snippetId, $shareWith)
			->willReturn($snippetShare);

		$this->snippetShareMapper->expects($this->once())
			->method('delete')
			->with($snippetShare);

		$this->snippetService->unshare($snippetId, $shareWith);
	}
}
