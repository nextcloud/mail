<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\InternalAddressController;
use OCA\Mail\Db\InternalAddress;
use OCP\AppFramework\Http;

class InternalAddressControllerTest extends TestCase {
	public function testSetAddressNullUser(): void {
		$serviceMock = $this->createServiceMock(InternalAddressController::class, [
			'userId' => null,
		]);

		$response = $serviceMock->getService()->setAddress('test@example.com', 'individual');

		self::assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testListCanBeRevalidated(): void {
		$serviceMock = $this->createServiceMock(InternalAddressController::class, [
			'userId' => 'alice',
		]);
		$internalAddress = new InternalAddress();
		$internalAddress->setAddress('example.test');
		$internalAddress->setType('domain');
		$serviceMock->getParameter('internalAddressService')
			->method('getInternalAddresses')
			->with('alice')
			->willReturn([$internalAddress]);

		$response = $serviceMock->getService()->list();

		self::assertNotNull($response->getETag());
		self::assertSame('private, no-cache', $response->getHeaders()['Cache-Control']);
	}
}
