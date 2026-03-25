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
	 * Normalize charset names for mbstring compatibility.
	 *
	 * Maps unsupported charset names to their mbstring equivalents.
	 * Notably, handles Korean encodings used by Outlook:
	 * - ks_c_5601-1987 and ks_c_5601-1989 are mapped to UHC (Windows-949/CP949)
	 *
	 * Charset tokens are case-insensitive in email headers (RFC 2046),
	 * so we normalize to lowercase for lookup.
	 */
	private function normalizeCharset(string $charset): string {
		$charset = trim($charset);
		$lowerCharset = strtolower($charset);

		// Map unsupported charsets to compatible alternatives
		// See: http://lists.w3.org/Archives/Public/ietf-charsets/2001AprJun/0030.html
		$charsetMap = [
			'ks_c_5601-1987' => 'UHC',
			'ks_c_5601-1989' => 'UHC',
		];

		return $charsetMap[$lowerCharset] ?? $charset;
	}

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
			$normalizedCharset = $this->normalizeCharset($charset);
			if (in_array($normalizedCharset, mb_list_encodings(), true)) {
				$converted = mb_convert_encoding($data, 'UTF-8', $normalizedCharset);
			} else {
				$converted = iconv($normalizedCharset, 'UTF-8', $data);
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

		$normalizedCharset = $charset !== null ? $this->normalizeCharset($charset) : null;
		$converted = @mb_convert_encoding($data, 'UTF-8', $normalizedCharset);
		if ($converted === false) {
			// Might be a charset that PHP mb doesn't know how to handle, fall back to iconv
			if ($normalizedCharset !== null) {
				$converted = iconv($normalizedCharset, 'UTF-8', $data);
			}
		}

		if (!is_string($converted)) {
			throw new ServiceException('Could not detect message charset');
		}
		return $converted;
	}
}
