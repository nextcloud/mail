<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Sieve;

class SieveLogger {
	/** @var resource */
	protected $stream;

	public function __construct(string $logFile) {
		$stream = @fopen($logFile, 'ab');
		if ($stream === false) {
			throw new \InvalidArgumentException('Unable to use "' . $logFile . '" as log file for sieve.');
		}
		$this->stream = $stream;
	}

	public function debug(string $message): void {
		fwrite($this->stream, $message);
	}

	public function __destruct() {
		fflush($this->stream);
		fclose($this->stream);
	}
}
