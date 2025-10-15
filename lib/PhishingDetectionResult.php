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
	public const TRUSTED_CHECK = 'Trusted';

	private string $message = '';
	private bool $isPhishing;
	private array $additionalData = [];
	private string $type;

	public function __construct(string $type, bool $isPhishing, string $message = '', array $additionalData = []) {
		$this->type = $type;
		$this->message = $message;
		$this->isPhishing = $isPhishing;
		$this->additionalData = $additionalData;

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
