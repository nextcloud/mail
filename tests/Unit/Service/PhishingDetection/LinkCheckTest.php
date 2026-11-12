<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\PhishingDetection;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\PhishingDetection\LinkCheck;
use OCP\IL10N;

class LinkCheckTest extends TestCase {
	private LinkCheck $check;
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->check = new LinkCheck($this->l10n);
	}

	public function testPlainTextWithoutLinksReturnsSafe(): void {
		$result = $this->check->run('<p>This is plain text without links</p>');

		$this->assertFalse($result->isPhishing());
	}

	public function testHtmlWithoutLinksReturnsSafe(): void {
		$html = '<html><body><p>This is plain text without links</p></body></html>';

		$result = $this->check->run($html);

		$this->assertFalse($result->isPhishing());
	}

	public function testLinkWithMatchingDomainReturnsSafe(): void {
		$html = '<html><body><a href="https://example.com/login">https://example.com/login</a></body></html>';

		$result = $this->check->run($html);

		$this->assertFalse($result->isPhishing());
	}

	public function testLinkWithMismatchedDomainReturnsWarning(): void {
		$html = '<html><body><a href="https://malicious.com">https://example.com</a></body></html>';

		$result = $this->check->run($html);

		$this->assertTrue($result->isPhishing());
	}

	public function testLinkTextNotLookingLikeUrlReturnsSafe(): void {
		$html = '<html><body><a href="https://malicious.com">Click here</a></body></html>';

		$result = $this->check->run($html);

		$this->assertFalse($result->isPhishing());
	}

	public function testMultipleLinksOnePhishingReturnsWarning(): void {
		$html = '<html><body><a href="https://example.com">https://example.com</a><a href="https://malicious.com">https://example.com</a></body></html>';

		$result = $this->check->run($html);

		$this->assertTrue($result->isPhishing());
	}

	public function testLinkWithBracketsReturnsSafe(): void {
		$html = '<html><body><a href="https://example.com">(https://example.com)</a></body></html>';

		$result = $this->check->run($html);

		$this->assertFalse($result->isPhishing());
	}

	public function testLinkWithQuotesReturnsSafe(): void {
		$html = '<html><body><a href="https://example.com">"https://example.com"</a></body></html>';

		$result = $this->check->run($html);

		$this->assertFalse($result->isPhishing());
	}
}
