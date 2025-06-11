<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use JsonSerializable;
use ReturnTypeWillChange;

final class Quota implements JsonSerializable {
	/** @var int */
	private $usage;

	/** @var int */
	private $limit;

	public function __construct(int $usage,
		int $limit) {
		$this->usage = $usage;
		$this->limit = $limit;
	}

	public function getUsage(): int {
		return $this->usage;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'usage' => $this->getUsage(),
			'limit' => $this->getLimit(),
		];
	}
}
