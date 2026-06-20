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
}
