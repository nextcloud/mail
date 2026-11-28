<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Exception;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use JmapClient\Exceptions\RequestException;
use JmapClient\Exceptions\TransportException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\JMAP\Exception\JmapTransportException;
use OCP\AppFramework\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class CouldNotConnectExceptionTest extends TestCase {
	private function requestException(int $status): RequestException {
		$response = $this->createMock(ResponseInterface::class);
		$response->method('getStatusCode')->willReturn($status);
		return new RequestException($this->createMock(RequestInterface::class), $response);
	}

	public function testJmapUnauthorizedIsWrongPassword(): void {
		$this->assertReason('AUTHENTICATION_WRONG_PASSWORD', $this->requestException(Http::STATUS_UNAUTHORIZED));
	}

	public function testJmapForbiddenIsDenied(): void {
		$this->assertReason('AUTHENTICATION_DENIED', $this->requestException(Http::STATUS_FORBIDDEN));
	}

	public function testJmapOtherStatusIsOther(): void {
		$this->assertReason('OTHER', $this->requestException(Http::STATUS_INTERNAL_SERVER_ERROR));
	}

	public function testJmapTransportFailureIsConnectionError(): void {
		$this->assertReason('CONNECTION_ERROR', new TransportException('timeout'));
		$this->assertReason('CONNECTION_ERROR', new JmapTransportException('timeout'));
	}

	public function testUnknownExceptionIsOther(): void {
		$this->assertReason('OTHER', new Exception('unknown'));
	}

	private function assertReason(string $expected, Throwable $previous): void {
		$exception = new CouldNotConnectException($previous, 'JMAP', 'jmap.example.com', 443);

		self::assertSame($expected, $exception->getReason());
	}
}
