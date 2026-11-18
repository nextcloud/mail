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
			$signatures = $this->createValidator($rawMessage)->validate();
		} catch (DKIMException|\TypeError) {
			// The library throws a TypeError when a selector's DNS record has no p= tag
			$signatures = [];
		}

		foreach ($signatures as $signature) {
			if ($signature[0]['status'] === 'SUCCESS') {
				return true;
			}
		}

		return false;
	}

	protected function createValidator(string $rawMessage): Validator {
		return new Validator($rawMessage);
	}
}
