<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getFileName()
 * @method void setFileName(string $fileName)
 * @method string getMimeType()
 * @method void setMimeType(string $mimeType)
 * @method int|null getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int|null getLocalMessageId()
 * @method void setLocalMessageId(int $localMessageId)
 */
class LocalAttachment extends Entity implements JsonSerializable {
	/** @var string */
	protected $userId;

	/** @var string */
	protected $fileName;

	/** @var string */
	protected $mimeType;

	/** @var int|null */
	protected $createdAt;

	/** @var int|null */
	protected $localMessageId;

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'type' => 'local',
			'fileName' => $this->fileName,
			'mimeType' => $this->mimeType,
			'createdAt' => $this->createdAt,
			'localMessageId' => $this->localMessageId
		];
	}
}
