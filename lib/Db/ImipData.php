<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setImipMessageId(int $messageId)
 * @method int getImipMessageId()
 * @method void setError(bool $error)
 * @method bool getError()
 * @method void setProcessedAt(?int $processedAt)
 * @method int|null getProcessedAt()
 */
class ImipData extends Entity {
	/** @var int */
	protected int $imipMessageId;

	/** @var bool */
	protected bool $error;

	/** @var int|null */
	protected ?int $processedAt = null;

	public function __construct() {
		$this->addType('imipMessageId', 'integer');
		$this->addType('error', 'boolean');
		$this->addType('processedAt', 'integer');
	}
}
