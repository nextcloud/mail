<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use JsonSerializable;
use Override;

final class DiscoveryEvent implements JsonSerializable {
	public const TYPE_PROBE = 'probe';
	public const TYPE_CANDIDATE = 'candidate';
	public const TYPE_COMPLETE = 'complete';

	public const STATUS_RUNNING = 'running';
	public const STATUS_FOUND = 'found';
	public const STATUS_NONE = 'none';

	public const SOURCE_AUTOCONFIG = 'autoconfig';
	public const SOURCE_SRV = 'srv';
	public const SOURCE_WELL_KNOWN = 'well-known';
	public const SOURCE_GUESS = 'guess';

	/**
	 * @param self::TYPE_* $type
	 * @param array<string, mixed> $data
	 */
	private function __construct(
		private string $type,
		private string $protocol,
		private array $data,
	) {
	}

	/**
	 * @param self::SOURCE_* $step
	 * @param self::STATUS_* $status
	 */
	public static function probe(string $protocol, string $step, string $status): self {
		return new self(self::TYPE_PROBE, $protocol, ['step' => $step, 'status' => $status]);
	}

	/**
	 * @param self::SOURCE_* $source
	 * @param array<string, JsonSerializable|null> $config
	 */
	public static function candidate(string $protocol, string $source, array $config): self {
		return new self(self::TYPE_CANDIDATE, $protocol, ['source' => $source, 'config' => $config]);
	}

	public static function complete(string $protocol, bool $found): self {
		return new self(self::TYPE_COMPLETE, $protocol, ['found' => $found]);
	}

	/**
	 * @return self::TYPE_*
	 */
	public function getType(): string {
		return $this->type;
	}

	public function getProtocol(): string {
		return $this->protocol;
	}

	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'type' => $this->type,
			'protocol' => $this->protocol,
			...$this->data,
		];
	}
}
