<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP\Threading;

use JsonSerializable;
use ReturnTypeWillChange;
use function str_replace;

class Message implements JsonSerializable {
	/** @var string */
	private $subject;

	/** @var string */
	private $id;

	/** @var string[] */
	private $references;

	/**
	 * @param string[] $references
	 */
	public function __construct(string $subject,
		string $id,
		array $references) {
		$this->subject = $subject;
		$this->id = $id;
		$this->references = $references;
	}

	public function hasReSubject(): bool {
		return str_starts_with($this->getSubject(), 'Re:');
	}

	public function getSubject(): string {
		return $this->subject;
	}

	public function getBaseSubject(): string {
		return str_replace('Re:', '', $this->getSubject());
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string[]
	 */
	public function getReferences(): array {
		return $this->references;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'subject' => $this->subject,
			'id' => $this->id,
			'references' => $this->references,
		];
	}
}
