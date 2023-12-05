<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getImapLabel()
 * @method void setImapLabel(string $imapLabel)
 * @method string getColor()
 * @method void setColor(string $color)
 * @method bool|null getIsDefaultTag()
 * @method void setIsDefaultTag(bool $flag)
 */
class Tag extends Entity implements JsonSerializable {
	protected $userId;
	protected $displayName;
	protected $imapLabel;
	protected $color;
	protected $isDefaultTag;

	public const LABEL_IMPORTANT = '$label1';
	public const LABEL_WORK = '$label2';
	public const LABEL_PERSONAL = '$label3';
	public const LABEL_TODO = '$label4';
	public const LABEL_LATER = '$label5';

	public function __construct() {
		$this->addType('isDefaultTag', 'boolean');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'displayName' => $this->getDisplayName(),
			'imapLabel' => $this->getImapLabel(),
			'color' => $this->getColor(),
			'isDefaultTag' => ($this->getIsDefaultTag() === true),
		];
	}
}
