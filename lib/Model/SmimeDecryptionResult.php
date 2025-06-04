<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

final class SmimeDecryptionResult {
	private string $decryptedMessage;
	private bool $isEncrypted;
	private bool $isSigned;
	private bool $isSignatureValid;

	public function __construct(string $decryptedMessage,
		bool $isEncrypted,
		bool $isSigned,
		bool $isSignatureValid) {
		$this->isEncrypted = $isEncrypted;
		$this->isSigned = $isSigned;
		$this->isSignatureValid = $isSignatureValid;
		$this->decryptedMessage = $decryptedMessage;
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
