<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ConnectivityTesterTest extends TestCase {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var ConnectivityTester */
	private $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->tester = new ConnectivityTester($this->logger);
	}

	public function testCanConnect() {
		$canConnect = $this->tester->canConnect('wikipedia.org', 80);

		$this->assertTrue($canConnect);
	}

	public function testCanNotConnect() {
		$before = microtime(true);
		$canConnect = $this->tester->canConnect('wikipedia.org', 90);
		$after = microtime(true);

		$this->assertFalse($canConnect);
		$this->assertLessThan(15, $after - $before);
	}

	public function testCanNotConnectToNonexistentDomain() {
		$before = microtime(true);
		$canConnect = $this->tester->canConnect('thisdomaindoesnotexist', 90);
		$after = microtime(true);

		$this->assertFalse($canConnect);
		$this->assertLessThan(15, $after - $before);
	}
}
