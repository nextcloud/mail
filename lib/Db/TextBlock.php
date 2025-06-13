<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getContent()
 * @method void setContent(string $content)
 * @method string getPreview()
 * @method void setPreview(string $preview)
 */
class TextBlock extends Entity implements JsonSerializable {
	protected $owner;
	protected $title;
	protected $content;
	protected $preview;

	public function __construct() {
		$this->addType('owner', 'string');
		$this->addType('title', 'string');
		$this->addType('content', 'string');
		$this->addType('preview', 'string');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'owner' => $this->getOwner(),
			'title' => $this->getTitle(),
			'content' => $this->getContent(),
			'preview' => $this->getPreview(),
		];
	}
}
