<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use OCP\ICertificateManager;
use OCP\ITempManager;

class SMimeService {
	private ITempManager $tempManager;
	private ICertificateManager $certificateManager;

	public function __construct(ITempManager $tempManager,
								ICertificateManager $certificateManager) {
		$this->tempManager = $tempManager;
		$this->certificateManager = $certificateManager;
	}

	/**
	 * Attempt to verify a message signed with S/MIME.
	 * Requires the openssl extension.
	 *
	 * @param string $message Whole message including all headers and parts as stored on IMAP
	 * @return bool
	 */
	public function verifyMessage(string $message): bool {
		// Ideally, we should use the more modern openssl cms module as it is a superset of the
		// smime/pkcs7 module. Unfortunately, it is only supported since php 8.
		// Ref https://www.php.net/manual/en/function.openssl-cms-verify.php

		$messageTemp = $this->tempManager->getTemporaryFile();
		$messageTempHandle = fopen($messageTemp, 'wb');
		fwrite($messageTempHandle, $message);
		fclose($messageTempHandle);
		/** @psalm-suppress NullArgument */
		$valid = openssl_pkcs7_verify($messageTemp, 0, null, [
			$this->certificateManager->getAbsoluteBundlePath(),
		]);
		if (is_int($valid)) {
			// OpenSSL error
			return false;
		}

		return $valid;
	}
}
