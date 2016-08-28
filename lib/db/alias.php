<?php

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setAccountId(string $accountId)
 * @method string getAccountId()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setAlias(string $alias)
 * @method string getAlias()
 */
class Alias extends Entity {
	
	public $accountId;
	public $name;
	public $alias;

}
