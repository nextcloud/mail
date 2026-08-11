<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use Rubix\ML\Encoding;
use Rubix\ML\Persisters\Persister;

class RubixMemoryPersister implements Persister {
	public function __construct(
		private string $data = '',
	) {
	}

	public function getData(): string {
		return $this->data;
	}

	#[\Override]
	public function save(Encoding $encoding): void {
		$this->data = $encoding->data();
	}

	#[\Override]
	public function load(): Encoding {
		return new Encoding($this->data);
	}

	public function __toString() {
		return self::class;
	}
}
