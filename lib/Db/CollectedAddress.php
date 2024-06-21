<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method null setUserId(string $userId)
 * @method string getUserId()
 * @method null setEmail(string $email)
 * @method string getEmail()
 * @method null setDisplayName(string $displayName)
 * @method string getDisplayName()
 */
class CollectedAddress extends Entity {
	protected $userId;
	protected $email;
	protected $displayName;
}
