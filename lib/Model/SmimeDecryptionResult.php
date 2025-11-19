<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

final class SmimeDecryptionResult {
	public function __construct(
		private readonly string $decryptedMessage,
		private readonly bool $isEncrypted,
		private readonly bool $isSigned,
		private readonly bool $isSignatureValid
	) {
	}

	public static function fromPlain(string $plainMessage): self {
		return new self($plainMessage, false, false, false);
	}

	public function getDecryptedMessage(): string {
		return $this->decryptedMessage;
	}

	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}

	public function isSigned(): bool {
		return $this->isSigned;
	}

	public function isSignatureValid(): bool {
		return $this->isSignatureValid;
	}
}
