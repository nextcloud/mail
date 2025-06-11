<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

final class MessageStructureData {
	/** @var bool */
	private $hasAttachments;

	/** @var string */
	private $previewText;

	/** @var bool */
	private $isImipMessage;

	private bool $isEncrypted;
	private bool $mentionsMe;

	public function __construct(bool $hasAttachments,
		string $previewText,
		bool $isImipMessage,
		bool $isEncrypted,
		bool $mentionsMe) {
		$this->hasAttachments = $hasAttachments;
		$this->previewText = $previewText;
		$this->isImipMessage = $isImipMessage;
		$this->isEncrypted = $isEncrypted;
		$this->mentionsMe = $mentionsMe;
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

	public function getMentionsMe(): bool {
		return $this->mentionsMe;
	}
}
