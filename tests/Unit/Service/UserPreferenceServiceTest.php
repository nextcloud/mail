<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\UserPreferenceService;
use OCP\IConfig;

class UserPreferenceServiceTest extends TestCase {
	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId = 'claire';

	/** @var UserPreferenceService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->service = new UserPreferenceService($this->config, $this->userId);
	}

	public function testGetPreference() {
		$this->config->expects($this->once())
			->method('getUserValue')
			->with($this->userId, 'mail', 'test', null)
			->willReturn('123');
		$expected = '123';

		$actual = $this->service->getPreference($this->userId, 'test');

		$this->assertEquals($expected, $actual);
	}

	public function testSetPreference() {
		$this->config->expects($this->once())
			->method('setUserValue')
			->with($this->userId, 'mail', 'test', '123')
			->willReturn('123');

		$this->service->setPreference($this->userId, 'test', '123');
	}
}
