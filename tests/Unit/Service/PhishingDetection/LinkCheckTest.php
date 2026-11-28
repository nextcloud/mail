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

final class LinkCheckTest extends TestCase {
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

	public function testLinkWithMismatchedDomainPreservesUtf8LinkText(): void {
		$html = '<p><a href="https://o-seznam.cz/napoveda/email/smluvni-podminky">Smluvními podmínkami služby Email.cz</a></p>';

		$result = $this->check->run($html);

		$this->assertTrue($result->isPhishing());
		$serialized = $result->jsonSerialize();
		$this->assertCount(1, $serialized['additionalData']);
		$this->assertSame('Smluvními podmínkami služby Email.cz', $serialized['additionalData'][0]['linkText']);
	}
}
