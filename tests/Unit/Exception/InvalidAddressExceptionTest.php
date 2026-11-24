<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Exception;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\InvalidAddressException;
use OCP\AppFramework\Http;

class InvalidAddressExceptionTest extends TestCase {
	public function testIsAValidationError(): void {
		$exception = new InvalidAddressException('Invalid domain');

		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $exception->getHttpCode());
	}
}
