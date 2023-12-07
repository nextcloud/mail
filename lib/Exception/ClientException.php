<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

namespace OCA\Mail\Exception;

use Exception;
use OCP\AppFramework\Http;
use Throwable;

class ClientException extends Exception {
	/**
	 * @param string $message [optional] The Exception message to throw.
	 * @param mixed $code [optional] The Exception code.
	 * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null) {
		if (!is_int($code)) {
			$code = (int)$code;
		}
		parent::__construct($message, $code, $previous);
	}

	public function getHttpCode(): int {
		return Http::STATUS_BAD_REQUEST;
	}
}
