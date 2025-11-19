<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Support;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Support\HostNameFactory;
use OCP\Util;

class HostNameFactoryTest extends TestCase {
	private ?\OCA\Mail\Support\HostNameFactory $factory = null;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new HostNameFactory();
	}

	public function testGetHostName(): void {
		$expected = Util::getServerHostName();

		$actual = $this->factory->getHostName();

		$this->assertSame($expected, $actual);
	}
}
