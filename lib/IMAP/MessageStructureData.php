<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

class MessageStructureData {
	/** @var bool */
	private $hasAttachments;

	/** @var string */
	private $previewText;

	/** @var bool */
	private $isImipMessage;

	private bool $isEncrypted;

	public function __construct(bool $hasAttachments,
		string $previewText,
		bool $isImipMessage,
		bool $isEncrypted) {
		$this->hasAttachments = $hasAttachments;
		$this->previewText = $previewText;
		$this->isImipMessage = $isImipMessage;
		$this->isEncrypted = $isEncrypted;
	}

	public function hasAttachments(): bool {
		return $this->hasAttachments;
	}

	public function getPreviewText(): string {
		return $this->previewText;
	}

	public function isImipMessage(): bool {
		return $this->isImipMessage;
	}

	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}
}
