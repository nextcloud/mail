<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Html2Text\Html2Text;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Db\TextBlockMapper;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Db\TextBlockShareMapper;
use OCA\Mail\Exception\ShareeAlreadyExistsException;
use OCA\Mail\Exception\UserNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroupManager;
use OCP\IUserManager;

class TextBlockService {

	public function __construct(
		private TextBlockMapper $textBlockMapper,
		private TextBlockShareMapper $textBlockShareMapper,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
	) {
	}

	/**
	 * @param string $userId
	 * @return TextBlock[]
	 */
	public function findAll(string $userId): array {
		return $this->textBlockMapper->findAll($userId);
	}

	/**
	 * @throws UserNotFoundException
	 */
	public function findAllSharedWithMe(string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new UserNotFoundException();
		}
		$groups = $this->groupManager->getUserGroupIds($user);
		return $this->textBlockMapper->findSharedWithMe($userId, $groups);
	}
	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $textBlockId, string $userId): ?TextBlock {
		return $this->textBlockMapper->find($textBlockId, $userId);
	}

	public function create(string $userId, string $title, string $content): TextBlock {
		$textBlock = new TextBlock();
		$textBlock->setContent($content);
		$textBlock->setOwner($userId);
		$textBlock->setTitle($title);
		$html = new Html2Text($content, ['do_links' => 'none','alt_image' => 'hide']);
		$preview = trim($html->getText());
		$textBlock->setPreview(substr($preview, 0, 50));
		return $this->textBlockMapper->insert($textBlock);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function update(TextBlock $textBlock, string $userId, string $title, string $content): TextBlock {
		$textBlock->setContent($content);
		$textBlock->setTitle($title);
		$html = new Html2Text($content, ['do_links' => 'none','alt_image' => 'hide']);
		$preview = trim($html->getText());
		$textBlock->setPreview(substr($preview, 0, 300));
		return $this->textBlockMapper->update($textBlock);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function delete(int $textBlockId, string $userId): void {
		$textBlock = $this->textBlockMapper->find($textBlockId, $userId);
		$this->textBlockMapper->delete($textBlock);
		$this->textBlockShareMapper->deleteByTextBlockId($textBlockId);
	}


	/**
	 * @throws UserNotFoundException
	 * @throws ShareeAlreadyExistsException
	 */
	public function share(int $textBlockId, string $shareWith): void {
		$sharee = $this->userManager->get($shareWith);
		if ($sharee === null) {
			throw new UserNotFoundException('Sharee does not exist');
		}
		if ($this->textBlockShareMapper->shareExists($textBlockId, $shareWith)) {
			throw new ShareeAlreadyExistsException();
		}
		$share = new TextBlockShare();
		$share->setShareWith($shareWith);
		$share->setTextBlockId($textBlockId);
		$share->setType(TextBlockShare::TYPE_USER);
		$this->textBlockShareMapper->insert($share);
	}

	public function getShares(int $textBlockId): array {
		$sharees = $this->textBlockShareMapper->findTextBlockShares($textBlockId);
		foreach ($sharees as $sharee) {
			if ($sharee->getType() === TextBlockShare::TYPE_GROUP) {
				$sharee->setDisplayName($sharee->getShareWith());
				continue;
			}
			$shareeUser = $this->userManager->get($sharee->getShareWith());
			if ($shareeUser === null) {
				continue;
			}
			$sharee->setDisplayName($shareeUser->getDisplayName());
		}
		return $sharees;
	}

	/**
	 * @throws UserNotFoundException
	 * @throws ShareeAlreadyExistsException
	 */
	public function shareWithGroup(int $textBlockId, string $groupId): void {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new UserNotFoundException('Group does not exist');
		}
		if ($this->textBlockShareMapper->shareExists($textBlockId, $groupId)) {
			throw new ShareeAlreadyExistsException();
		}
		$share = new TextBlockShare();
		$share->setShareWith($groupId);
		$share->setTextBlockId($textBlockId);
		$share->setType(TextBlockShare::TYPE_GROUP);
		$this->textBlockShareMapper->insert($share);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function unshare(int $textBlockId, string $shareWith): void {
		$share = $this->textBlockShareMapper->find($textBlockId, $shareWith);
		$this->textBlockShareMapper->delete($share);
	}
}
