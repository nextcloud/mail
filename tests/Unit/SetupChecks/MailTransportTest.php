<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\SetupChecks;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\L10N\L10N;
use OCA\Mail\SetupChecks\MailTransport;
use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;

class MailTransportTest extends TestCase {
	private IConfig&MockObject $config;
	private IL10N&MockObject $l10n;
	private MailTransport $check;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(L10N::class);

		$this->check = new MailTransport($this->config, $this->l10n);
	}

	public function testSuccess(): void {
		$this->config->method('getSystemValueString')
			->willReturn('smtp');

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public function testWarning(): void {
		$this->config->method('getSystemValueString')
			->willReturn('mail');

		$result = $this->check->run();

		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
	}

	public function testName(): void {
		$this->l10n->method('t')
			->willReturn('Translated Name');

		$this->assertEquals('Translated Name', $this->check->getName());
	}

	public function testCategory(): void {
		$this->assertEquals('mail', $this->check->getCategory());
	}
}
