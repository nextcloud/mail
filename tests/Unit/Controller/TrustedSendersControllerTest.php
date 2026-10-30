<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\TrustedSendersController;
use OCA\Mail\Db\TrustedSender;
use OCP\AppFramework\Http;

class TrustedSendersControllerTest extends TestCase {
	public function testSetTrustedNullUser(): void {
		$serviceMock = $this->createServiceMock(TrustedSendersController::class, [
			'userId' => null,
		]);

		$response = $serviceMock->getService()->setTrusted('sender@example.com', 'individual');

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testRemoveTrustNullUser(): void {
		$serviceMock = $this->createServiceMock(TrustedSendersController::class, [
			'userId' => null,
		]);

		$response = $serviceMock->getService()->removeTrust('sender@example.com', 'individual');

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testListNullUser(): void {
		$serviceMock = $this->createServiceMock(TrustedSendersController::class, [
			'userId' => null,
		]);

		$response = $serviceMock->getService()->list();

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testListCanBeRevalidated(): void {
		$serviceMock = $this->createServiceMock(TrustedSendersController::class, [
			'userId' => 'alice',
		]);
		$trustedSender = new TrustedSender();
		$trustedSender->setEmail('example.test');
		$trustedSender->setType('domain');
		$serviceMock->getParameter('trustedSenderService')
			->method('getTrusted')
			->with('alice')
			->willReturn([$trustedSender]);

		$response = $serviceMock->getService()->list();

		self::assertNotNull($response->getETag());
		self::assertSame('private, no-cache', $response->getHeaders()['Cache-Control']);
	}
}
