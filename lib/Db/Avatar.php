<?php

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
 * @method void setUserId(string $userId)
 * @method string getUserId()
 * @method void setEmail(string $email)
 * @method string getEmail()
 * @method void setSource(string $source)
 * @method string getSource()
 * @method void setUrl(string $url)
 * @method string getUrl()
 * @method void setUpdatedAt(string $updatedAt)
 * @method string getUpdatedAt()
 */
class Avatar extends Entity {

	public $userId;
	public $email;
	public $source;
	public $url;
	public $updatedAt;

}
