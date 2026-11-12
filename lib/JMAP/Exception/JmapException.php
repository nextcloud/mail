<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP\Exception;

use JmapClient\Responses\ResponseException;
use OCA\Mail\Exception\ServiceException;
use Throwable;

/**
 * Thrown when the JMAP server rejects a request or its outcome is unknown.
 */
class JmapException extends ServiceException {
	private const TRANSIENT_TYPES = [
		ResponseException::SERVER_FAIL,
		ResponseException::SERVER_UNAVAILABLE,
		ResponseException::SERVER_PARTIAL_FAIL,
		'rateLimit',
	];

	/**
	 * @param string $type JMAP error type reported by the server
	 * @param bool $outcomeUncertain true when the server might have processed the request
	 */
	public function __construct(
		string $message,
		private string $type,
		private bool $outcomeUncertain = false,
		?Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);
	}

	public function getType(): string {
		return $this->type;
	}

	public function isOutcomeUncertain(): bool {
		return $this->outcomeUncertain;
	}

	/**
	 * Whether repeating the same request might succeed.
	 */
	public function isTransient(): bool {
		return !$this->outcomeUncertain && in_array($this->type, self::TRANSIENT_TYPES, true);
	}
}
