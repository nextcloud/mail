<?php

declare(strict_types=1);

/**
 * @author Gregor Mitzka <gregor.mitzka@gmail.com>
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

namespace OCA\Mail\Service\Avatar;

use Closure;
use Exception;
use OCA\Mail\Exception\ServiceException;

class DnsRecordService {
	/**
	 * @param string $hostname
	 * @param int $type (optional)
	 *
	 * @return array
	 *
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	public function getRecords(
		string $hostname,
		int $type = DNS_ANY
	): array {
		return $this->catchError(
			function () use ($hostname, $type) {
				return (
					dns_get_record(
						$hostname,
						$type
					)
				);
			}
		);
	}

	/**
	 * @param \Closure $callback
	 *
	 * @return mixed
	 *
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	private function catchError(Closure $callback) {
		set_error_handler(
			function (
				int $_errno,
				string $errstr
			): bool {
				restore_error_handler();

				// e.g. $errstr = "dns_get_record(): An unexpected server failure occurred."
				list(, $message) = explode(':', $errstr, 2);

				throw new ServiceException(
					trim($message)
				);
			}
		);

		try {
			return $callback();
		} finally {
			restore_error_handler();
		}
	}
}
