<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

class CollectedAddress extends Entity {

	protected $userId;
	protected $email;

}
