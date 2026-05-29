<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Smtp;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\SMTP\LenientSmtphordeTransport;

class LenientSmtphordeTransportTest extends TestCase {
	public function testPrepareHeadersAllowsDomainsStartingWithDigit(): void {
		$transport = new LenientSmtphordeTransport();

		[$from, $headers] = $transport->prepareHeaders([
			'From' => 'Test User <user@180grad-kiel.de>',
			'Subject' => 'Regression test',
		]);

		$this->assertSame('user@180grad-kiel.de', $from);
		$this->assertStringContainsString('From: Test User <user@180grad-kiel.de>', $headers);
	}
}
