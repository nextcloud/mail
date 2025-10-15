<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Exception;

use Exception;
use Throwable;

class ServiceException extends Exception {
	/**
	 * @param string $message [optional] The Exception message to throw.
	 * @param mixed $code [optional] The Exception code.
	 * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct($message = '', $code = 0, ?Throwable $previous = null) {
		if (!is_int($code)) {
			$code = (int)$code;
		}
		parent::__construct($message, $code, $previous);
	}
}
