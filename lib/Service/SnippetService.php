<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Html2Text\Html2Text;
use OCA\Mail\Db\Snippet;
use OCA\Mail\Db\SnippetMapper;
use OCA\Mail\Db\SnippetShare;
use OCA\Mail\Db\SnippetShareMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\NotPermittedException;
use OCP\IGroupManager;
use OCP\IUserManager;

class SnippetService {

	private SnippetMapper $snippetMapper;
	private SnippetShareMapper $snippetShareMapper;
	private IUserManager $userManager;
	private IGroupManager $groupManager;

	public function __construct(SnippetMapper $snippetMapper,
		SnippetShareMapper $snippetShareMapper,
		IGroupManager $groupManager,
		IUserManager $userManager) {
		$this->snippetMapper = $snippetMapper;
		$this->snippetShareMapper = $snippetShareMapper;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $userId
	 * @return Snippet[]
	 */
	public function findAll(string $userId): array {
		return $this->snippetMapper->findAll($userId);
	}

	/**
	 * @param string $userId
	 * @return Snippet[]
	 * @throws DoesNotExistException
	 */
	public function findAllSharedWithMe(string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new DoesNotExistException('User does not exist');
		}
		$groups = $this->groupManager->getUserGroupIds($user);
		return $this->snippetMapper->findSharedWithMe($userId, $groups);
	}
	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @return Snippet|null
	 */
	public function find(int $snippetId, string $userId): ?Snippet {
		return $this->snippetMapper->find($snippetId, $userId);
	}

	/**
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @return Snippet
	 */
	public function create(string $userId, string $title, string $content): Snippet {
		$snippet = new Snippet();
		$snippet->setContent($content);
		$snippet->setOwner($userId);
		$snippet->setTitle($title);
		$html = new Html2Text($content, ['do_links' => 'none','alt_image' => 'hide']);
		$preview = trim($html->getText());
		$snippet->setPreview(substr($preview, 0, 50));
		return $this->snippetMapper->insert($snippet);
	}

	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @return Snippet
	 */
	public function update(int $snippetId, string $userId, string $title, string $content): Snippet {
		$snippet = $this->snippetMapper->find($snippetId, $userId);
		if ($snippet === null) {
			throw new DoesNotExistException('Snippet does not exist');
		}
		$snippet->setContent($content);
		$snippet->setTitle($title);
		$html = new Html2Text($content, ['do_links' => 'none','alt_image' => 'hide']);
		$preview = trim($html->getText());
		$snippet->setPreview(substr($preview, 0, 300));
		return $this->snippetMapper->update($snippet);
	}

	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @throws DoesNotExistException
	 */
	public function delete(int $snippetId, string $userId): void {
		$snippet = $this->snippetMapper->find($snippetId, $userId);
		if ($snippet === null) {
			throw new DoesNotExistException('Snippet does not exist');
		}
		$this->snippetMapper->delete($snippet);
		$this->snippetShareMapper->deleteBySnippetId($snippetId);
	}


	/**
	 * @param int $snippetId
	 * @param string $shareWith
	 * @throws DoesNotExistException
	 * @throws NotPermittedException
	 */
	public function share(int $snippetId, string $shareWith): void {

		$sharee = $this->userManager->get($shareWith);
		if ($sharee === null) {
			throw new DoesNotExistException('Sharee does not exist');
		}
		if ($this->snippetShareMapper->shareExists($snippetId, $shareWith)) {
			throw new NotPermittedException('Share already exists');
		}
		$share = new SnippetShare();
		$share->setShareWith($shareWith);
		$share->setSnippetId($snippetId);
		$share->setType(SnippetShare::TYPE_USER);
		$this->snippetShareMapper->insert($share);
	}

	public function getShares(int $snippetId): array {
		$sharees = $this->snippetShareMapper->findSnippetShares($snippetId);
		foreach ($sharees as $sharee) {
			if ($sharee->getType() === SnippetShare::TYPE_GROUP) {
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
	 * @param int $snippetId
	 * @param string $groupId
	 * @throws DoesNotExistException
	 * @throws NotPermittedException
	 */
	public function shareWithGroup(int $snippetId, string $groupId): void {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new DoesNotExistException('Group does not exist');
		}
		if ($this->snippetShareMapper->shareExists($snippetId, $groupId)) {
			throw new NotPermittedException('Share already exists');
		}
		$share = new SnippetShare();
		$share->setShareWith($groupId);
		$share->setSnippetId($snippetId);
		$share->setType(SnippetShare::TYPE_GROUP);
		$this->snippetShareMapper->insert($share);
	}

	/**
	 * @param int $snippetId
	 * @param string $shareWith
	 * @throws DoesNotExistException
	 */
	public function unshare(int $snippetId, string $shareWith): void {
		$share = $this->snippetShareMapper->find($snippetId, $shareWith);
		$this->snippetShareMapper->delete($share);
	}


}
