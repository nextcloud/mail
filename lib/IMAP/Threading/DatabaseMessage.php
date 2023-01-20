<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\IMAP\Threading;

use JsonSerializable;
use ReturnTypeWillChange;
use function array_map;
use function array_merge;
use function json_decode;

class DatabaseMessage extends Message implements JsonSerializable {
	/** @var int */
	private $databaseId;

	/** @var string|null */
	private $threadRootId;

	/** @var bool */
	private $dirty = false;

	public function __construct(int $databaseId,
								string $subject,
								string $id,
								array $references,
								?string $threadRootId) {
		parent::__construct($subject, $id, $references);

		$this->databaseId = $databaseId;
		$this->threadRootId = $threadRootId;
	}

	public static function fromRowData(int $id,
									   string $subject,
									   ?string $messageId,
									   ?string $references,
									   ?string $inReplyTo,
									   ?string $threadRootId): self {
		$referencesForThreading = $references !== null ? json_decode($references, true) : [];
		if (!empty($inReplyTo)) {
			$referencesForThreading[] = $inReplyTo;
		}

		return new self(
			$id,
			$subject,
			$messageId,
			$referencesForThreading,
			$threadRootId
		);
	}

	public function getDatabaseId(): int {
		return $this->databaseId;
	}

	public function getThreadRootId(): ?string {
		return $this->threadRootId;
	}

	public function setThreadRootId(?string $threadRootId): void {
		// Only update the thread ID if it has a value, is different and we haven't set one before
		if ($threadRootId !== null && $this->threadRootId !== $threadRootId && !$this->dirty) {
			$this->dirty = true;
			$this->threadRootId = $threadRootId;
		}
	}

	public function isDirty(): bool {
		return $this->dirty;
	}

	public function redact(callable $hash): DatabaseMessage {
		return new self(
			$this->databaseId,
			$this->hasReSubject() ? "Re: " . $hash($this->getSubject()) : $hash($this->getSubject()),
			$hash($this->getId()),
			array_map(static function (string $ref) use ($hash) {
				return $hash($ref);
			}, $this->getReferences()),
			$this->threadRootId === null ? null : $hash($this->threadRootId)
		);
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_merge(
			parent::jsonSerialize(),
			[
				'databaseId' => $this->databaseId,
				'threadRootId' => $this->getThreadRootId(),
			]
		);
	}
}
