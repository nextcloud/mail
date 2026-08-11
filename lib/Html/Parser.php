<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Html;

use DOMDocument;
use function libxml_clear_errors;
use function libxml_use_internal_errors;

class Parser {

	/**
	 * Parse a DOM document from a string
	 *
	 * @psalm-param non-empty-string $html
	 *
	 * DOMDocument uses libxml to parse HTML and that expects HTML4. It
	 * is likely that some markup cause an error, which is otherwise
	 * logged. Therefore, we ignore any error here.
	 * @todo Migrate to \Dom\HTMLDocument::createFromString when this app uses PHP8.4+
	 */
	public static function parseToDomDocument(string $html): DOMDocument {
		$document = new DOMDocument();
		$previousLibxmlErrorsState = libxml_use_internal_errors(true);
		$document->loadHTML($html, LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
		libxml_clear_errors();
		libxml_use_internal_errors($previousLibxmlErrorsState);
		return $document;
	}

}
