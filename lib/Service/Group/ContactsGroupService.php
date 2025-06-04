<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Group;

use OCP\Contacts\IManager;
use OCP\IConfig;

class ContactsGroupService implements IGroupService {
	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	/**
	 * Group's namespace
	 *
	 * @var string
	 */
	private $namespace = 'Contacts';

	public function __construct(IManager $contactsManager, IConfig $config) {
		$this->contactsManager = $contactsManager;
		$this->config = $config;
	}

	#[\Override]
	public function getNamespace(): string {
		return $this->namespace;
	}

	#[\Override]
	public function search(string $term): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$allowSystemUsers = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') === 'yes';

		$result = $this->contactsManager->search($term, ['CATEGORIES']);
		$receivers = [];
		foreach ($result as $r) {
			if (!$allowSystemUsers && isset($r['isLocalSystemBook']) && $r['isLocalSystemBook']) {
				continue;
			}
			if (!isset($r['EMAIL'])) {
				continue;
			}
			foreach (explode(',', $r['CATEGORIES'] ?? '') as $group) {
				$receivers[] = [
					'id' => $group,
					'name' => $group
				];
			}
		}
		return array_unique($receivers, SORT_REGULAR);
	}

	#[\Override]
	public function getUsers(string $groupId): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$allowSystemUsers = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') === 'yes';

		$result = $this->contactsManager->search($groupId, ['CATEGORIES']);
		$receivers = [];
		foreach ($result as $r) {
			if (!$allowSystemUsers && isset($r['isLocalSystemBook']) && $r['isLocalSystemBook']) {
				continue;
			}
			if (!isset($r['EMAIL'])) {
				continue;
			}
			$groups = explode(',', $r['CATEGORIES']);

			// search matches substring but we only want full match
			if (!in_array($groupId, $groups)) {
				continue;
			}

			$emails = $r['EMAIL'];
			if (!is_array($emails)) {
				$emails = [$emails];
			}
			foreach ($emails as $email) {
				$receivers[] = [
					'email' => $email
				];
			}
		}
		return $receivers;
	}
}
