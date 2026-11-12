<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP\Exception;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Responses\ResponseException;
use OCA\Mail\JMAP\Exception\JmapException;

class JmapExceptionTest extends TestCase {
	public static function transientProvider(): array {
		return [
			'server failure' => [ResponseException::SERVER_FAIL, false, true],
			'server unavailable' => [ResponseException::SERVER_UNAVAILABLE, false, true],
			'partial server failure' => [ResponseException::SERVER_PARTIAL_FAIL, false, true],
			'rate limit' => ['rateLimit', false, true],
			'permanent rejection' => ['forbiddenToSend', false, false],
			'unknown type' => ['somethingNew', false, false],
			'uncertain outcome' => [ResponseException::SERVER_FAIL, true, false],
		];
	}

	/**
	 * @dataProvider transientProvider
	 */
	public function testIsTransient(string $type, bool $outcomeUncertain, bool $expected): void {
		$exception = new JmapException('', $type, $outcomeUncertain);

		$this->assertSame($expected, $exception->isTransient());
	}
}
