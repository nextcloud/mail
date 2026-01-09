<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Charset;

use Horde_Mime_Part;
use OCA\Mail\Exception\ServiceException;
use ZBateson\MbWrapper\MbWrapper;
use ZBateson\MbWrapper\UnsupportedCharsetException;
use function is_string;

class Converter {
	/**
	 * Prioritized charsets used for detection if header is missing/wrong.
	 * This list can be expanded/tweaked based on userbase/email sources/field experience.
	 */
    private const DETECTION_CHARSETS = [
        'UTF-8',
        'WINDOWS-1252',
        'ISO-8859-1',
        'ISO-8859-15',
        'ISO-8859-2',
        'KOI8-R',
        'KOI8-U',
        'ISO-8859-5',
        // Add locale/userbase-specific encodings as needed.
    ];

	private MbWrapper $mbWrapper;

	public function __construct(?MbWrapper $mbWrapper = null) {
		$this->mbWrapper = $mbWrapper ?: new MbWrapper();
	}

	/**
	 * Converts the contents of a MIME part to UTF-8 using charset normalization,
	 * detection, and fallback logic for email compatibility.
	 *
	 * @param Horde_Mime_Part $p The MIME part to convert.
	 * @return string The UTF-8 encoded content.
	 * @throws ServiceException If charset detection or conversion fails.
	 */
	public function convert(Horde_Mime_Part $p): string {
		/** @var null|string $data */
		$data = $p->getContents();
		if (!is_string($data) || $data === '') {
			return '';
		}

		$charset = $p->getCharset();
		// Try header-declared charset first, if any.
		//
		// We always do one conversion attempt even if UTF-8 is indicated (and before detection) since:
		// - headers can lie
		// - some encodings may pass as "valid" UTF-8 by accident
		// - we want to surface problems
		if ($charset !== null && $charset !== '') {
			try {
				return $this->mbWrapper->convert($data, $charset, 'UTF-8');
			} catch (UnsupportedCharsetException $e) {
				// fall through to detection & fallback
			}
		}

		// If already valid UTF-8, return as-is
		if ($this->mbWrapper->checkEncoding($data, 'UTF-8')) {
			return $data;
		}

		// Try prioritised detection list
		$detectedCharset = mb_detect_encoding($data, self::DETECTION_CHARSETS, true);
		if ($detectedCharset !== false && strtoupper($detectedCharset) !== 'UTF-8') {
			try {
				return $this->mbWrapper->convert($data, $detectedCharset, 'UTF-8');
			} catch (UnsupportedCharsetException $e) {
				// fall through
			}
		}

		// Try most common Western fallback charsets manually
		$fallbacks = ['WINDOWS-1252', 'ISO-8859-1'];
		foreach ($fallbacks as $fallbackCharset) {
			try {
				return $this->mbWrapper->convert($data, $fallbackCharset, 'UTF-8');
			} catch (UnsupportedCharsetException $e) {
				// continue
			}
		}

		// If nothing succeeded, throw a rich exception for debugging
		$head = is_string($data) ? $data : var_export($data, true); // better safe than sorry
		$head = preg_replace('/[^\x20-\x7E\n\r\t]/', '?', $head); // binary/non-printable characters
		if (mb_strlen($head) > 40) { // truncate to a sample of $data
			$head = mb_substr($head, 0, 40) . '...';
		}
		throw new ServiceException(sprintf(
			'Could not detect or convert message charset (input type: %s, charset: %s, head: %s)',
			gettype($data),
			var_export($charset, true),
			$head
		));
	}
}
