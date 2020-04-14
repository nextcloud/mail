<?php

declare(strict_types=1);

/**
 * @author Jakob Sack <mail@jakobsack.de>
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

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setAccountId(int $accountId)
 * @method int getAccountId()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setAlias(string $alias)
 * @method string getAlias()
 */
class Alias extends Entity {

	/** @var int */
	protected $accountId;

	/** @var string */
	protected $name;

	/** @var string */
	protected $alias;

	public function __construct() {
		$this->addType('accountId', 'int');
		$this->addType('name', 'string');
		$this->addType('alias', 'string');
	}
}
