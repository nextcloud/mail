<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use OCA\Mail\Controller\SettingsController;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Tests\Integration\TestCase;
use OCA\Mail\Tests\Unit\AuditLog\AuditEvents;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class SettingsControllerTest extends TestCase {
	/** @var ServiceMockObject */
	private $mock;

	/** @var SettingsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(SettingsController::class);
		$this->controller = $this->mock->getService();
	}

	public function testProvisioning() {
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('provision')
			->with();

		$response = $this->controller->provision();

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testCreateProvisioning(): void {
		$provisioning = new Provisioning();
		$this->mock->getParameter('provisioningManager')
			->expects(self::once())
			->method('newProvisioning')
			->willReturn($provisioning);

		$response = $this->controller->createProvisioning([]);

		self::assertEquals(new JSONResponse($provisioning), $response);
	}

	public function testDeprovision() {
		$provisioning = new Provisioning();
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('getConfigById')
			->willReturn($provisioning);
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('deprovision')
			->with($provisioning);
		$response = $this->controller->deprovision(1);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	private function expectAuditEvent(string $operation, string $message, array $parameters): void {
		$this->mock->getParameter('eventDispatcher')
			->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(fn (CriticalActionPerformedEvent $event): bool => AuditEvents::hasOperation($event, $operation)
				&& $event->getLogMessage() === $message
				&& $event->getParameters() === $parameters));
	}

	public function testSetAntiSpamEmail(): void {
		$this->mock->getParameter('antiSpamService')
			->expects(self::once())
			->method('setSpamEmail')
			->with('spam@example.com');
		$this->mock->getParameter('antiSpamService')
			->expects(self::once())
			->method('setHamEmail')
			->with('ham@example.com');
		$this->expectAuditEvent(
			'mail.antispam_config.updated',
			'Anti-spam report addresses were set to "%s" for spam and "%s" for not spam',
			['spamAddress' => 'spam@example.com', 'hamAddress' => 'ham@example.com'],
		);

		$this->controller->setAntiSpamEmail('spam@example.com', 'ham@example.com');
	}

	public function testDeleteAntiSpamEmail(): void {
		$this->mock->getParameter('antiSpamService')
			->expects(self::once())
			->method('deleteConfig');
		$this->expectAuditEvent('mail.antispam_config.deleted', 'Anti-spam report addresses were removed', []);

		$this->controller->deleteAntiSpamEmail();
	}

	public function testSetAllowNewMailAccounts(): void {
		$this->mock->getParameter('appConfig')
			->expects(self::once())
			->method('setValueBool');
		$this->expectAuditEvent(
			'mail.allow_new_accounts.updated',
			'Users were no longer allowed to add their own mail accounts',
			['allowed' => false],
		);

		$this->controller->setAllowNewMailAccounts(false);
	}

	public function testSetEnabledLlmProcessing(): void {
		$this->mock->getParameter('appConfig')
			->expects(self::once())
			->method('setValueBool');
		$this->expectAuditEvent(
			'mail.llm_processing.updated',
			'Processing mail content with large language models was enabled',
			['enabled' => true],
		);

		$this->controller->setEnabledLlmProcessing(true);
	}
}
