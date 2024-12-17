<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;

use OCA\Mail\Service\PhishingDetection\LinkCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class LinkCheckTest extends TestCase {

	private IL10N&MockObject $l10n;
	private LinkCheck|MockObject $service;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->service = new LinkCheck($this->l10n);
	}

	public function testNakedAddressPass(): void {
		$htmlMessage = '<html><body><a href="https://nextcloud.com/">nextcloud.com</a></p></body></html>';

		$result = $this->service->run($htmlMessage);

		$this->assertFalse($result->isPhishing());
	}

	public function testCompleteAddressPass(): void {
		$htmlMessage = '<html><body><a href="https://nextcloud.com/">https://nextcloud.com/</a></body></html>';

		$result = $this->service->run($htmlMessage);

		$this->assertFalse($result->isPhishing());
	}

	public function testAddressInParenthesessPass(): void {
		$htmlMessage = '<html><body><a href="https://nextcloud.com/">(https://nextcloud.com/)</a></body></html>';

		$result = $this->service->run($htmlMessage);

		$this->assertFalse($result->isPhishing());
	}

	public function testCompleteAddressFail(): void {
		$htmlMessage = '<html><body><a href="https://nextcloud.com/">https://google.com/</a></p></body></html>';

		$result = $this->service->run($htmlMessage);

		$this->assertTrue($result->isPhishing());
	}

	public function testDeepAddressPass(): void {
		$htmlMessage = '<html><body><a href="https://nextcloud.com/"><span class="text-big" style="color:hsl(0,75%,60%);font-family: Courier, monospace;"><i><strong>nextcloud.com</strong></i></span></a></body></html>';

		$result = $this->service->run($htmlMessage);

		$this->assertFalse($result->isPhishing());
	}
}
