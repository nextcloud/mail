<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Sieve;

final class NamedSieveScript {
	public function __construct(
		private ?string $name,
		private string $script,
	) {
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getScript(): string {
		return $this->script;
	}
}
