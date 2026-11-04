<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager as IShareManager;

/**
 * Applies the instance-wide sharing restrictions to Mail objects shared with users and groups.
 */
class SharingRestrictionService {
	public function __construct(
		private IShareManager $shareManager,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
	) {
	}

	public function canShareWithUser(string $sharerId, IUser $sharee): bool {
		if (!$this->isSharingAllowed($sharerId)) {
			return false;
		}
		if (!$this->shareManager->shareWithGroupMembersOnly()) {
			return true;
		}
		$commonGroups = array_diff(
			array_intersect(
				$this->groupManager->getUserGroupIds($sharee),
				$this->getUserGroupIds($sharerId),
			),
			$this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList(),
		);
		return $commonGroups !== [];
	}

	public function canShareWithGroup(string $sharerId, string $groupId): bool {
		if (!$this->isSharingAllowed($sharerId) || !$this->shareManager->allowGroupSharing()) {
			return false;
		}
		if (!$this->shareManager->shareWithGroupMembersOnly()) {
			return true;
		}
		return in_array($groupId, $this->getUserGroupIds($sharerId), true)
			&& !in_array($groupId, $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList(), true);
	}

	private function isSharingAllowed(string $sharerId): bool {
		return $this->shareManager->shareApiEnabled()
			&& !$this->shareManager->sharingDisabledForUser($sharerId);
	}

	/**
	 * @return string[]
	 */
	private function getUserGroupIds(string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return [];
		}
		return $this->groupManager->getUserGroupIds($user);
	}
}
