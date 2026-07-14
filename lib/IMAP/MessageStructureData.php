<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

final class MessageStructureData {
	public function __construct(
		private bool $hasAttachments,
		private string $previewText,
		private bool $isImipMessage,
		private bool $isEncrypted,
		private bool $mentionsMe,
		private ?string $governanceLabelHeader = null,
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

	public function getGovernanceLabelHeader(): ?string {
		return $this->governanceLabelHeader;
	}
}
