<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCP\IGroupManager;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager as IShareManager;
use function class_exists;

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
		// TODO: drop the legacy checks when the least compatible server version is 34
		if ($this->hasInteractionApi()) {
			/** @psalm-suppress InvalidArgument UserReceiver only satisfies InteractionReceiver on newer OCP versions */
			return $this->isInteractionAllowed($sharerId, new UserReceiver($sharee->getUID(), $sharee));
		}

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
		// TODO: drop the legacy checks when the least compatible server version is 34
		if ($this->hasInteractionApi()) {
			/** @psalm-suppress InvalidArgument GroupReceiver only satisfies InteractionReceiver on newer OCP versions */
			return $this->isInteractionAllowed($sharerId, new GroupReceiver($groupId));
		}

		if (!$this->isSharingAllowed($sharerId) || !$this->shareManager->allowGroupSharing()) {
			return false;
		}
		if (!$this->shareManager->shareWithGroupMembersOnly()) {
			return true;
		}
		return in_array($groupId, $this->getUserGroupIds($sharerId), true)
			&& !in_array($groupId, $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList(), true);
	}

	/**
	 * RestrictInteractionEvent is available since Nextcloud 34.0.2
	 */
	protected function hasInteractionApi(): bool {
		return class_exists(RestrictInteractionEvent::class);
	}

	private function isInteractionAllowed(string $sharerId, InteractionReceiver $receiver): bool {
		$event = new RestrictInteractionEvent(
			$sharerId,
			$this->userManager->get($sharerId),
			[],
			new ShareAction(),
			[$receiver],
		);
		return $event->isInteractionRestricted() === false;
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
