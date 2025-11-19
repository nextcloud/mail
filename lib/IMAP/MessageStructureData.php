<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

final class MessageStructureData {
	public function __construct(
		private readonly bool $hasAttachments,
		private readonly string $previewText,
		private readonly bool $isImipMessage,
		private readonly bool $isEncrypted,
		private readonly bool $mentionsMe
	) {
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
