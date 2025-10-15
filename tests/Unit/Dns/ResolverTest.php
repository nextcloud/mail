<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Dns;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Dns\Resolver;

class ResolverTest extends TestCase {
	private Resolver $resolver;

	protected function setUp(): void {
		parent::setUp();

		$this->resolver = new Resolver();
	}

	public function domainSplitData(): array {
		return [
			['nextcloud.com', false],
			['test.ac.at', false],
			['ac.at', true],
		];
	}

	/**
	 * @dataProvider domainSplitData
	 */
	public function testGetSecondLevelDomain(string $hostname, bool $expectedResult): void {
		$result = $this->resolver->isSuffix($hostname);

		self::assertSame($expectedResult, $result);
	}
}
