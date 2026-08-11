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
 * @method string|null getContentId()
 * @method void setContentId(?string $contentId)
 * @method string|null getDisposition()
 * @method void setDisposition(?string $disposition)
 * @method int|null getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int|null getLocalMessageId()
 * @method void setLocalMessageId(int $localMessageId)
 */
class LocalAttachment extends Entity implements JsonSerializable {
	public const DISPOSITION_ATTACHMENT = 'attachment';
	public const DISPOSITION_INLINE = 'inline';
	public const DISPOSITION_OMIT = null;

	/** @var string */
	protected $userId;

	/** @var string */
	protected $fileName;

	/** @var string */
	protected $mimeType;

	/** @var ?string */
	protected $contentId;

	/** @var ?string */
	protected $disposition;

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
			'contentId' => $this->contentId,
			'disposition' => $this->disposition,
			'createdAt' => $this->createdAt,
			'localMessageId' => $this->localMessageId
		];
	}

	public function isDispositionAttachmentOrInline(): bool {
		return $this->disposition === self::DISPOSITION_ATTACHMENT || $this->disposition === self::DISPOSITION_INLINE;
	}
}
