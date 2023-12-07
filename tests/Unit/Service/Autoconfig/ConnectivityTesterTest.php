<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
