<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Snippet;
use OCA\Mail\Db\SnippetMapper;
use OCA\Mail\Db\SnippetShare;
use OCA\Mail\Db\SnippetShareMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\NotPermittedException;
use OCP\IGroupManager;
use OCP\IUserManager;

class SnippetService {

	/** @var SnippetMapper */
	private $snippetMapper;

	/** @var SnippetShareMapper */
	private $snippetShareMapper;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(SnippetMapper $snippetMapper, SnippetShareMapper $snippetShareMapper, IUserManager $userManager, IGroupManager $groupManager) {
		$this->snippetMapper = $snippetMapper;
		$this->snippetShareMapper = $snippetShareMapper;
		$this->userManager = $userManager;
		$this->$groupManager = $groupManager;
	}

	/**
	 * @param string $userId
	 * @return Snippet[]
	 */
	public function findAll(string $userId): array {
		return $this->snippetMapper->findAll($userId);
	}

	/**
	 * @param string
	 */
	public function findAllSharedWithMe(string $userId): array {
		$groups = $this->groupManager->getUserGroupIds($userId);
		return $this->snippetShareMapper->findSharedWithMe($userId, $groups);
	}
	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @return Snippet
	 * @throws DoesNotExistException
	 */
	public function find(int $snippetId, string $userId): Snippet {
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
		return $this->snippetMapper->insert($snippet);
	}

	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @param string $title
	 * @param string $content
	 * @return Snippet
	 * @throws DoesNotExistException
	 */
	public function update(int $snippetId, string $userId, string $title, string $content): Snippet {
		$snippet = $this->snippetMapper->find($snippetId, $userId);
		$snippet->setContent($content);
		$snippet->setTitle($title);
		return $this->snippetMapper->update($snippet);
	}

	/**
	 * @param int $snippetId
	 * @param string $userId
	 * @throws DoesNotExistException
	 */
	public function delete(int $snippetId, string $userId): void {
		$snippet = $this->snippetMapper->find($snippetId, $userId);
		$this->snippetMapper->delete($snippet);
	}

	//TODO: run owner check on controller level
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

	public function unshare(int $snippetId, string $shareWith): void {
		$share = $this->snippetShareMapper->find($snippetId, $shareWith);
		$this->snippetShareMapper->delete($share);
	}


}
