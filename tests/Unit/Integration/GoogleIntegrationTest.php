<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Integration\GoogleIntegration;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class GoogleIntegrationTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private GoogleIntegration $integration;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(GoogleIntegration::class);
		$this->integration = $this->serviceMock->getService();
	}

	private function expectAuditEvent(string $operation, string $message, array $parameters): void {
		$this->serviceMock->getParameter('eventDispatcher')
			->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === $operation
				&& $event->getLogMessage() === $message
				&& $event->getParameters() === $parameters));
	}

	public function testConfigureLogsClientIdWithoutSecret(): void {
		$this->serviceMock->getParameter('crypto')
			->method('encrypt')
			->with('secret')
			->willReturn('encrypted');
		$this->serviceMock->getParameter('appConfig')
			->expects(self::exactly(2))
			->method('setValueString');
		$this->expectAuditEvent(
			'mail.google_oauth.configured',
			'Google OAuth client "%s" was configured for mail accounts',
			['clientId' => 'client-id'],
		);

		$this->integration->configure('client-id', 'secret');
	}

	public function testUnlink(): void {
		$this->serviceMock->getParameter('appConfig')
			->expects(self::exactly(2))
			->method('deleteKey');
		$this->expectAuditEvent('mail.google_oauth.deleted', 'Google OAuth client for mail accounts was removed', []);

		$this->integration->unlink();
	}
}
