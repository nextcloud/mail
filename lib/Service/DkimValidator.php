<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IDkimValidator;
use PHPMailer\DKIMValidator\DKIMException;
use PHPMailer\DKIMValidator\Validator;

class DkimValidator implements IDkimValidator {
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
