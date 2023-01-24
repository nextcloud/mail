<?php

declare(strict_types=1);

/**
 * @author Matthias Rella <mrella@pisys.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Recipient;
use OCA\Mail\Service\Group\ContactsGroupService;
use OCA\Mail\Service\Group\IGroupService;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCA\Mail\Exception\ServiceException;
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
					'label' => $g['name'] . " (" . $gs->getNamespace() . ")",
					'email' => $gid,
					'photo' => null,
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
		return strtolower($gs->getNamespace()) . ":";
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
					if (empty($members)) {
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
