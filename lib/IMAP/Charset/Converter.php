<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Charset;

use Horde_Mime_Part;
use OCA\Mail\Exception\ServiceException;
use function in_array;
use function is_string;

class Converter {

	/**
	 * @param Horde_Mime_Part $p
	 * @return string
	 * @throws ServiceException
	 */
	public function convert(Horde_Mime_Part $p): string {
		/** @var null|string $data */
		$data = $p->getContents();
		if ($data === null) {
			return '';
		}

		// Only convert encoding if it is explicitly specified in the header because text/calendar
		// data is utf-8 by default.
		$charset = $p->getCharset();
		if ($charset !== null && strtoupper($charset) === 'UTF-8') {
			return $data;
		}

		// The part specifies a charset
		if ($charset !== null) {
			if (in_array($charset, mb_list_encodings(), true)) {
				$converted = mb_convert_encoding($data, 'UTF-8', $charset);
			} else {
				$converted = iconv($charset, 'UTF-8', $data);
			}

			if (is_string($converted)) {
				return $converted;
			}
		}

		// No charset specified, let's ask mb if this could be UTF-8
		$detectedCharset = mb_detect_encoding($data, 'UTF-8', true);
		if ($detectedCharset === false) {
			// Fallback, non UTF-8
			$detectedCharset = mb_detect_encoding($data, null, true);
		}
		// Still UTF8, no need to convert
		if ($detectedCharset !== false && strtoupper($detectedCharset) === 'UTF-8') {
			return $data;
		}

		$converted = @mb_convert_encoding($data, 'UTF-8', $charset);
		if ($converted === false) {
			// Might be a charset that PHP mb doesn't know how to handle, fall back to iconv
			$converted = iconv($charset, 'UTF-8', $data);
		}

		if (!is_string($converted)) {
			throw new ServiceException('Could not detect message charset');
		}
		return $converted;
	}
}
