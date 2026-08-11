<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;
use OCP\AppFramework\Http;

class ValidationException extends Exception {
	/** @var bool[] */
	private $fields;

	public function __construct() {
		$this->fields = [];
	}

	public function getHttpCode(): int {
		return Http::STATUS_BAD_REQUEST;
	}

	public function getFields(): array {
		return $this->fields;
	}

	public function setField(string $key, bool $validates): void {
		$this->fields[$key] = $validates;
	}

	public function setFields(array $fields): void {
		foreach ($fields as $key => $validates) {
			$this->setField($key, $validates);
		}
	}
}
