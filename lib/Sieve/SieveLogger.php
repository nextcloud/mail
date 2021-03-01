<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
