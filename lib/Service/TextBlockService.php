<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Html2Text\Html2Text;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Db\TextBlockMapper;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Db\TextBlockShareMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\NotPermittedException;
use OCP\IGroupManager;
use OCP\IUserManager;

class TextBlockService {

	private TextBlockMapper $textBlockMapper;
	private TextBlockShareMapper $textBlockShareMapper;
	private IUserManager $userManager;
	private IGroupManager $groupManager;

	public function __construct(TextBlockMapper $textBlockMapper,
		TextBlockShareMapper $textBlockShareMapper,
		IGroupManager $groupManager,
		IUserManager $userManager) {
		$this->textBlockMapper = $textBlockMapper;
		$this->textBlockShareMapper = $textBlockShareMapper;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $userId
	 * @return TextBlock[]
	 */
	public function findAll(string $userId): array {
		return $this->textBlockMapper->findAll($userId);
	}

	/**
	 * @param string $userId
	 * @return TextBlock[]
	 * @throws DoesNotExistException
	 */
	public function findAllSharedWithMe(string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new DoesNotExistException('User does not exist');
		}
		$groups = $this->groupManager->getUserGroupIds($user);
		return $this->textBlockMapper->findSharedWithMe($userId, $groups);
	}
	/**
	 * @param int $textBlockId
	 * @param string $userId
	 * @return TextBlock|null
	 */
	public function find(int $textBlockId, string $userId): ?TextBlock {
		return $this->textBlockMapper->find($textBlockId, $userId);
	}

	/**
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @return TextBlock
	 */
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
	 * @param int $textBlockId
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @return TextBlock
	 */
	public function update(int $textBlockId, string $userId, string $title, string $content): TextBlock {
		$textBlock = $this->textBlockMapper->find($textBlockId, $userId);
		if ($textBlock === null) {
			throw new DoesNotExistException('TextBlock does not exist');
		}
		$textBlock->setContent($content);
		$textBlock->setTitle($title);
		$html = new Html2Text($content, ['do_links' => 'none','alt_image' => 'hide']);
		$preview = trim($html->getText());
		$textBlock->setPreview(substr($preview, 0, 300));
		return $this->textBlockMapper->update($textBlock);
	}

	/**
	 * @param int $textBlockId
	 * @param string $userId
	 * @throws DoesNotExistException
	 */
	public function delete(int $textBlockId, string $userId): void {
		$textBlock = $this->textBlockMapper->find($textBlockId, $userId);
		if ($textBlock === null) {
			throw new DoesNotExistException('TextBlock does not exist');
		}
		$this->textBlockMapper->delete($textBlock);
		$this->textBlockShareMapper->deleteByTextBlockId($textBlockId);
	}


	/**
	 * @param int $textBlockId
	 * @param string $shareWith
	 * @throws DoesNotExistException
	 * @throws NotPermittedException
	 */
	public function share(int $textBlockId, string $shareWith): void {

		$sharee = $this->userManager->get($shareWith);
		if ($sharee === null) {
			throw new DoesNotExistException('Sharee does not exist');
		}
		if ($this->textBlockShareMapper->shareExists($textBlockId, $shareWith)) {
			throw new NotPermittedException('Share already exists');
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
	 * @param int $textBlockId
	 * @param string $groupId
	 * @throws DoesNotExistException
	 * @throws NotPermittedException
	 */
	public function shareWithGroup(int $textBlockId, string $groupId): void {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new DoesNotExistException('Group does not exist');
		}
		if ($this->textBlockShareMapper->shareExists($textBlockId, $groupId)) {
			throw new NotPermittedException('Share already exists');
		}
		$share = new TextBlockShare();
		$share->setShareWith($groupId);
		$share->setTextBlockId($textBlockId);
		$share->setType(TextBlockShare::TYPE_GROUP);
		$this->textBlockShareMapper->insert($share);
	}

	/**
	 * @param int $textBlockId
	 * @param string $shareWith
	 * @throws DoesNotExistException
	 */
	public function unshare(int $textBlockId, string $shareWith): void {
		$share = $this->textBlockShareMapper->find($textBlockId, $shareWith);
		$this->textBlockShareMapper->delete($share);
	}


}
