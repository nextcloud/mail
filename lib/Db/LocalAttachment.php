<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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

	#[\Override]
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
