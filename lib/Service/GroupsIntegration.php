<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Group\ContactsGroupService;
use OCA\Mail\Service\Group\IGroupService;
use OCA\Mail\Service\Group\NextcloudGroupService;
use function array_map;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function OCA\Mail\array_flat_map;

class GroupsIntegration {
	/**
	 * The services to get groups from
	 *
	 * @var IGroupService[]
	 */
	private $groupServices = [];

	public function __construct(ContactsGroupService $contactsGroupService, NextcloudGroupService $nextcloudGroupService) {
		$this->groupServices = [
			$contactsGroupService,
			$nextcloudGroupService,
		];
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $term
	 * @return array
	 */
	public function getMatchingGroups(string $term): array {
		$receivers = [];
		foreach ($this->groupServices as $gs) {
			$result = $gs->search($term);
			foreach ($result as $g) {
				$gid = $this->servicePrefix($gs) . $g['id'];
				$receivers[] = [
					'id' => $gid,
					'label' => $g['name'] . ' (' . $gs->getNamespace() . ')',
					'email' => $gid,
					'source' => 'groups',
				];
			}
		}

		return $receivers;
	}

	/**
	 * Returns the prefix for the group service.
	 *
	 * @param IGroupService $gs
	 * @return string
	 */
	public function servicePrefix(IGroupService $gs): string {
		if (empty($gs->getNamespace())) {
			throw new ServiceException('GroupService has no namespace');
		}
		return strtolower($gs->getNamespace()) . ':';
	}

	/**
	 * Expands group names to its members
	 *
	 * @param Recipient[] $recipients
	 *
	 * @return Recipient[]
	 */
	public function expand(array $recipients): array {
		return array_flat_map(function (Recipient $recipient) {
			foreach ($this->groupServices as $service) {
				if (mb_strpos($recipient->getEmail(), $this->servicePrefix($service)) !== false) {
					$groupId = mb_substr(
						$recipient->getEmail(),
						mb_strlen($this->servicePrefix($service))
					);
					$members = array_filter($service->getUsers($groupId), static function (array $member) {
						return !empty($member['email']);
					});
					if ($members === []) {
						throw new ServiceException($groupId . " ({$service->getNamespace()}) has no members with email addresses");
					}
					return array_map(static function (array $member) use ($recipient) {
						return Recipient::fromParams([
							'messageId' => $recipient->getMessageId(),
							'type' => $recipient->getType(),
							'label' => $member['name'] ?? $member['email'],
							'email' => $member['email'],
						]);
					}, $members);
				}
			}

			return [$recipient];
		}, $recipients);
	}
}
