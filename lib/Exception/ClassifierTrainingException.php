<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Throwable;

class ClassifierTrainingException extends ServiceException {
	public function __construct($message = 'Classifier training failed',
		$code = 0,
		?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
