<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\AutoConfig;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
final class Configuration implements JsonSerializable {
	private ?ServerConfiguration $imapConfig;
	private ?ServerConfiguration $smtpConfig;

	public function __construct(?ServerConfiguration $imapConfig,
		?ServerConfiguration $smtpConfig) {
		$this->imapConfig = $imapConfig;
		$this->smtpConfig = $smtpConfig;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'imapConfig' => $this->imapConfig,
			'smtpConfig' => $this->smtpConfig,
		];
	}

	public function getImapConfig(): ?ServerConfiguration {
		return $this->imapConfig;
	}

	public function getSmtpConfig(): ?ServerConfiguration {
		return $this->smtpConfig;
	}
}
