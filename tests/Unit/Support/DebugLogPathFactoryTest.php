<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Support;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Support\DebugLogPathFactory;
use OCP\IConfig;

class DebugLogPathFactoryTest extends TestCase {
	private IConfig|\PHPUnit\Framework\MockObject\MockObject $config;
	private DebugLogPathFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->with('datadirectory')
			->willReturn('/data');

		$this->factory = new DebugLogPathFactory($this->config);
	}

	private function account(bool $accountDebug, ?int $id = 5): Account {
		$mailAccount = new MailAccount();
		if ($id !== null) {
			$mailAccount->setId($id);
		}
		$mailAccount->setUserId('bob');
		$mailAccount->setDebug($accountDebug);

		return new Account($mailAccount);
	}

	public function testIsActiveWhenAccountDebugIsOn(): void {
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(false);

		$this->assertTrue($this->factory->isActive($this->account(true)));
	}

	public function testIsActiveWhenSystemWideDebugIsOn(): void {
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(true);

		$this->assertTrue($this->factory->isActive($this->account(false)));
	}

	public function testNotActiveWhenNeitherFlagIsOn(): void {
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(false);

		$this->assertFalse($this->factory->isActive($this->account(false)));
	}

	public function testGetPathReturnsNullWhenNotActive(): void {
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(false);

		$this->assertNull($this->factory->getPath($this->account(false), 'imap'));
	}

	public function testGetPathReturnsPathWhenActive(): void {
		$this->config->method('getSystemValueBool')
			->with('app.mail.debug')
			->willReturn(false);

		$this->assertSame('/data/mail-bob-5-imap.log', $this->factory->getPath($this->account(true), 'imap'));
	}

	public function testBuildPathUsesPlaceholderWhenAccountHasNoIdYet(): void {
		$this->assertSame('/data/mail-bob-new-smtp.log', $this->factory->buildPath($this->account(false, null), 'smtp'));
	}

	public function testBuildPathUsesTheAccountId(): void {
		$this->assertSame('/data/mail-bob-5-sieve.log', $this->factory->buildPath($this->account(false), 'sieve'));
	}
}
