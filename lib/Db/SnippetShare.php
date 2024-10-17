<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getType()
 * @method void setType(string $type)
 * @method string getShareWith()
 * @method void setShareWith((string $shareWith)
 * @method string getSnippetId()
 * @method void setSnippetId(string $snippetId)
 */
class SnippetShare extends Entity implements JsonSerializable {
	protected $owner;
	protected $title;
	protected $content;

	public function __construct() {
		$this->addType('type', 'string');
		$this->addType('shareWith', 'string');
		$this->addType('snippetId', 'string');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'shareWith' => $this->getShareWith(),
			'snippetId' => $this->getSnippetId(),
		];
	}
}
