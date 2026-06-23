<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\SvgSanitizer;

class SvgSanitizerTest extends TestCase {
	private SvgSanitizer $sanitizer;

	protected function setUp(): void {
		parent::setUp();

		$this->sanitizer = new SvgSanitizer();
	}

	public function testRemovesScript(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('<script', $result);
		$this->assertStringContainsString('<rect', $result);
	}

	public function testRemovesEventHandlers(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect onload="evil()" width="10"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('onload', $result);
		$this->assertStringContainsString('width="10"', $result);
	}

	public function testRemovesExternalReference(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
			. '<a xlink:href="https://evil.example"><rect/></a></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('evil.example', $result);
		$this->assertStringContainsString('<rect', $result);
	}

	public function testKeepsSameDocumentReference(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
			. '<use xlink:href="#icon"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringContainsString('#icon', $result);
	}

	public function testRejectsDoctypeAndEntities(): void {
		$svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
			. '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';

		$this->assertSame('', $this->sanitizer->sanitize($svg));
	}

	public function testReturnsEmptyForInvalidMarkup(): void {
		$this->assertSame('', $this->sanitizer->sanitize('this is not <<< svg'));
		$this->assertSame('', $this->sanitizer->sanitize(''));
	}

	public function testKeepsSafeGraphics(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
			. '<circle cx="5" cy="5" r="4" fill="red"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringContainsString('<circle', $result);
		$this->assertStringContainsString('fill="red"', $result);
	}

	public function testRemovesSrcAttribute(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<image src="https://tracker.example/pixel.gif" width="1" height="1"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('tracker.example', $result);
		$this->assertStringContainsString('width="1"', $result);
	}

	public function testStripsExternalUrlFromStyleAttribute(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<rect style="fill: url(https://tracker.example/img.png)" width="10"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('tracker.example', $result);
		$this->assertStringContainsString('width="10"', $result);
	}

	public function testKeepsSameDocumentUrlInStyleAttribute(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<defs><linearGradient id="g"><stop offset="0" stop-color="red"/></linearGradient></defs>'
			. '<rect style="fill: url(#g)" width="10"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringContainsString('url(#g)', $result);
	}

	public function testStripsExternalUrlFromStyleElement(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<style>rect { fill: url(https://tracker.example/img.png) }</style>'
			. '<rect width="10"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringNotContainsString('tracker.example', $result);
		$this->assertStringContainsString('<rect', $result);
	}

	public function testKeepsSafeStyleRules(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<style>rect { fill: red; stroke: blue }</style>'
			. '<rect width="10"/></svg>';

		$result = $this->sanitizer->sanitize($svg);

		$this->assertStringContainsString('fill: red', $result);
	}

	public function testRejectsOversizedInput(): void {
		$svg = str_repeat('a', 2 * 1024 * 1024 + 1);

		$this->assertSame('', $this->sanitizer->sanitize($svg));
	}

	public function testLooksLikeSvgWithDirectSvgTag(): void {
		$this->assertTrue($this->sanitizer->looksLikeSvg('<svg xmlns="http://www.w3.org/2000/svg"/>'));
	}

	public function testLooksLikeSvgWithXmlPrologue(): void {
		$this->assertTrue($this->sanitizer->looksLikeSvg('<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"/>'));
	}

	public function testLooksLikeSvgReturnsFalseForHtml(): void {
		$this->assertFalse($this->sanitizer->looksLikeSvg('<!DOCTYPE html><html><body><svg/></body></html>'));
	}

	public function testLooksLikeSvgReturnsFalseForRasterImage(): void {
		$this->assertFalse($this->sanitizer->looksLikeSvg("\x89PNG\r\n"));
	}

	public function testLooksLikeSvgReturnsFalseForHtmlComment(): void {
		$this->assertFalse($this->sanitizer->looksLikeSvg('<!-- comment --><svg/>'));
	}
}
