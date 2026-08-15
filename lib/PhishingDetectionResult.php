<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
final class PhishingDetectionResult implements JsonSerializable {

	public const DATE_CHECK = 'Date';
	public const LINK_CHECK = 'Link';
	public const REPLYTO_CHECK = 'Reply-To';
	public const CUSTOM_EMAIL_CHECK = 'Custom Email';
	public const CONTACTS_CHECK = 'Contacts';
	public const IMAP_FLAG_CHECK = 'IMAP Flag';
	public const TRUSTED_CHECK = 'Trusted';

	public function __construct(
		private string $type,
		private bool $isPhishing,
		private string $message = '',
		private array $additionalData = [],
	) {
	}

	public function getType(): string {
		return $this->type;
	}

	public function isPhishing(): bool {
		return $this->isPhishing;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'type' => $this->type,
			'isPhishing' => $this->isPhishing,
			'message' => $this->message,
			'additionalData' => $this->additionalData,
		];
	}

}
