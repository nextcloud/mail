<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IDkimValidator;
use PHPMailer\DKIMValidator\DKIMException;
use PHPMailer\DKIMValidator\Validator;

class DkimValidator implements IDkimValidator {
	#[\Override]
	public function validate(string $rawMessage): bool {
		try {
			$validator = new Validator($rawMessage);
			$signatures = $validator->validate();
		} catch (DKIMException) {
			$signatures = [];
		}

		foreach ($signatures as $signature) {
			if ($signature[0]['status'] === 'SUCCESS') {
				return true;
			}
		}

		return false;
	}
}
