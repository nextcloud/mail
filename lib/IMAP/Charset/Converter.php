<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Charset;

use Horde_Mime_Part;
use OCA\Mail\Exception\ServiceException;
use ValueError;
use function in_array;
use function is_string;

class Converter {

	/**
	 * Map of unsupported charset names to their mbstring equivalents.
	 * Keys must be lowercase for case-insensitive lookup.
	 *
	 * @see http://lists.w3.org/Archives/Public/ietf-charsets/2001AprJun/0030.html
	 */
	private const CHARSET_MAP = [
		'ks_c_5601-1987' => 'UHC',
		'ks_c_5601-1989' => 'UHC',
	];

	/**
	 * Normalize charset names for mbstring compatibility.
	 *
	 * Maps unsupported charset names to their mbstring equivalents.
	 * Notably, handles Korean encodings used by Outlook:
	 * - ks_c_5601-1987 and ks_c_5601-1989 are mapped to UHC (Windows-949/CP949)
	 *
	 * Charset tokens are case-insensitive in email headers (RFC 2045),
	 * so we normalize to lowercase for lookup.
	 */
	private function normalizeCharset(string $charset): string {
		$charset = trim($charset);
		$lowerCharset = strtolower($charset);

		return self::CHARSET_MAP[$lowerCharset] ?? $charset;
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
			try {
				if (in_array($normalizedCharset, mb_list_encodings(), true)) {
					$converted = mb_convert_encoding($data, 'UTF-8', $normalizedCharset);
				} else {
					$converted = @iconv($normalizedCharset, 'UTF-8', $data);
				}
			} catch (ValueError) {
				// Invalid charset name, treat as null to use auto-detection below
				$charset = null;
				$converted = null;
			}

			if (is_string($converted)) {
				return $converted;
			}
		}

		// No charset specified, let's ask mb if this could be UTF-8
		$detectedCharset = mb_detect_encoding($data, 'UTF-8', true);
		if ($detectedCharset === false) {
			// Fallback, try common charsets (the default mb_detect_encoding order may miss some)
			$detectedCharset = mb_detect_encoding($data, 'ISO-8859-1,ISO-8859-2,UTF-8,ASCII', true);
		}
		// Still UTF8, no need to convert
		if ($detectedCharset !== false && strtoupper($detectedCharset) === 'UTF-8') {
			return $data;
		}

		// Use detected charset when available, otherwise use original/normalized charset
		if ($detectedCharset !== false) {
			$sourceCharset = $detectedCharset;
		} elseif ($charset !== null) {
			$sourceCharset = $this->normalizeCharset($charset);
		} else {
			$sourceCharset = null;
		}

		// Attempt conversion with the source charset
		try {
			$converted = @mb_convert_encoding($data, 'UTF-8', $sourceCharset);
		} catch (ValueError) {
			$converted = false;
		}

		if ($converted === false) {
			// Might be a charset that PHP mb doesn't know how to handle, fall back to iconv
			try {
				$converted = @iconv($sourceCharset, 'UTF-8', $data);
			} catch (ValueError) {
				// Invalid charset, conversion not possible
				$converted = null;
			}
		}

		if (!is_string($converted)) {
			throw new ServiceException('Could not convert message charset');
		}
		return $converted;
	}
}
