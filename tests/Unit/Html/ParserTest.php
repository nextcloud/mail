<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Html;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DOMDocument;
use OCA\Mail\Html\Parser;

class ParserTest extends TestCase {
	public function testParseToDomDocument(): void {
		$html = '<html><body><p>Test content</p></body></html>';

		$doc = Parser::parseToDomDocument($html);

		$this->assertInstanceOf(DOMDocument::class, $doc);
	}

	public function testParseMinimalHtml(): void {
		$html = '<html></html>';

		$doc = Parser::parseToDomDocument($html);

		$this->assertInstanceOf(DOMDocument::class, $doc);
	}

	public function testParseMalformedHtml(): void {
		$html = '<p>Unclosed paragraph';

		$doc = Parser::parseToDomDocument($html);

		$this->assertInstanceOf(DOMDocument::class, $doc);
	}

	public function testParseHtmlWithSpecialCharacters(): void {
		$html = '<html><body><p>Test & special < > chars</p></body></html>';

		$doc = Parser::parseToDomDocument($html);

		$this->assertInstanceOf(DOMDocument::class, $doc);
	}

	public function testParseComplexHtml(): void {
		$html = '<html><head><title>Test</title></head><body><div class="container"><p>Content</p></div></body></html>';

		$doc = Parser::parseToDomDocument($html);

		$this->assertInstanceOf(DOMDocument::class, $doc);
		$this->assertNotNull($doc->documentElement);
	}
}
