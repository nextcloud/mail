<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setError(bool $error)
 * @method bool getError()
 * @method void setProcessedAt(?int $processedAt)
 * @method int|null getProcessedAt()
 * @method string|null getErrorMessage()
 */
class ImipData extends Entity {

	/** @var bool */
	protected $error;

	/** @var int|null */
	protected $processedAt;

	/** @var string */
	protected $errorMessage;

	public function __construct() {
		$this->addType('error', 'boolean');
		$this->addType('processedAt', 'integer');
		$this->addType('errorMessage', 'string');
	}
}
