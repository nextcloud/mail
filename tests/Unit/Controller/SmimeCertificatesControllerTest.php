<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\SmimeCertificatesController;
use OCP\AppFramework\Http;

class SmimeCertificatesControllerTest extends TestCase {
	public function testIndexNullUser(): void {
		$serviceMock = $this->createServiceMock(SmimeCertificatesController::class, [
			'appName' => 'mail',
			'userId' => null,
		]);

		$response = $serviceMock->getService()->index();

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testDestroyNullUser(): void {
		$serviceMock = $this->createServiceMock(SmimeCertificatesController::class, [
			'appName' => 'mail',
			'userId' => null,
		]);

		$response = $serviceMock->getService()->destroy(1);

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testCreateNullUser(): void {
		$serviceMock = $this->createServiceMock(SmimeCertificatesController::class, [
			'appName' => 'mail',
			'userId' => null,
		]);

		$response = $serviceMock->getService()->create();

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}
}
