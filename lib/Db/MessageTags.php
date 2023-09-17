<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author 2023 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
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
 * @method string getImapMessageId()
 * @method void setImapMessageId(string $imapMessageId)
 * @method int getTagId()
 * @method void setTagId(int $tagId)
 */
class MessageTags extends Entity implements JsonSerializable {
	protected $imapMessageId;
	protected $tagId;

	public function __construct() {
		$this->addType('tagId', 'integer');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'imapMessageId' => $this->getImapMessageId(),
			'tagId' => $this->getTagId(),
		];
	}
}
