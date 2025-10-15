<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Group;

interface IGroupService {
	/**
	 * Search the service's groups.
	 *
	 * @param string $term
	 * @return array of matched groups as associative arrays
	 */
	public function search(string $term): array;

	/**
	 * Get the group's namespace.
	 *
	 * @return string
	 */
	public function getNamespace(): string;

	/**
	 * Get the group's users.
	 *
	 * @param string $groupId
	 * @return array with group's users as associative arrays
	 */
	public function getUsers(string $groupId): array;
}
