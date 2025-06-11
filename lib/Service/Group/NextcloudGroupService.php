<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Group;

use OCA\Mail\Exception\ServiceException;
use OCP\IConfig;
use OCP\IGroupManager;

class NextcloudGroupService implements IGroupService {
	/**
	 * Nextcloud's group manager
	 *
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Group's namespace
	 *
	 * @var string
	 */
	private $namespace = 'Nextcloud';

	public function __construct(IGroupManager $groupManager, IConfig $config) {
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	#[\Override]
	public function getNamespace(): string {
		return $this->namespace;
	}

	#[\Override]
	public function search(string $term): array {
		$c1 = $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes');
		$c2 = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no');
		if ($c1 !== 'yes'
			|| $c2 !== 'no') {
			return [];
		}
		$groups = $this->groupManager->search($term);

		return array_map(
			static function ($g) {
				return [
					'id' => $g->getGID(),
					'name' => $g->getDisplayName()
				];
			},
			$groups
		);
	}

	#[\Override]
	public function getUsers(string $groupId): array {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new ServiceException("$groupId ({$this->getNamespace()}) does not exist");
		}
		$users = $this->groupManager->get($groupId)->getUsers();
		return array_map(
			static function ($user) {
				return [
					'id' => $user->getUID(),
					'name' => $user->getDisplayName(),
					'email' => $user->getEMailAddress()
				];
			},
			$users
		);
	}
}
