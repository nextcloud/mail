<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\OutOfOffice;

use DateTimeImmutable;
use JsonSerializable;
use ReturnTypeWillChange;

class OutOfOfficeState implements JsonSerializable {
	public const DEFAULT_VERSION = 1;

	public function __construct(
		private bool $enabled,
		private ?DateTimeImmutable $start,
		private ?DateTimeImmutable $end,
		private string $subject,
		private string $message,
		private int $version = self::DEFAULT_VERSION,
	) {
	}

	public static function fromJson(array $data): self {
		return new self(
			$data['enabled'],
			isset($data['start']) ? new DateTimeImmutable($data['start']) : null,
			isset($data['end']) ? new DateTimeImmutable($data['end']) : null,
			$data['subject'],
			$data['message'],
			$data['version'],
		);
	}

	public function getVersion(): int {
		return $this->version;
	}

	public function setVersion(int $version): void {
		$this->version = $version;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void {
		$this->enabled = $enabled;
	}

	public function getStart(): ?DateTimeImmutable {
		return $this->start;
	}

	public function setStart(?DateTimeImmutable $start): void {
		$this->start = $start;
	}

	public function getEnd(): ?DateTimeImmutable {
		return $this->end;
	}

	public function setEnd(?DateTimeImmutable $end): void {
		$this->end = $end;
	}

	public function getSubject(): string {
		return $this->subject;
	}

	public function setSubject(string $subject): void {
		$this->subject = $subject;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function setMessage(string $message): void {
		$this->message = $message;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$json = [
			'version' => $this->getVersion(),
			'enabled' => $this->isEnabled(),
		];

		$start = $this->getStart();
		if ($start) {
			$json['start'] = $start->format('c');
		}

		$end = $this->getEnd();
		if ($end) {
			$json['end'] = $end->format('c');
		}

		$json['subject'] = $this->getSubject();
		$json['message'] = $this->getMessage();
		return $json;
	}
}
