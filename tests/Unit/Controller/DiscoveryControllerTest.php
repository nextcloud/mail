<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Generator;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Controller\DiscoveryController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\DiscoveryService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\StreamTraversableResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;

class DiscoveryControllerTest extends TestCase {
	private DiscoveryService&MockObject $discoveryService;
	private IRemoteHostValidator&MockObject $hostValidator;
	private IAppConfig&MockObject $appConfig;
	private DiscoveryController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->discoveryService = $this->createMock(DiscoveryService::class);
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->controller = new DiscoveryController(
			$this->createMock(IRequest::class),
			$this->discoveryService,
			$this->hostValidator,
			$this->appConfig,
		);
	}

	private function allowNewAccounts(bool $allowed): void {
		$this->appConfig->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS, true)
			->willReturn($allowed);
	}

	public function testDisabledAccountCreation(): void {
		$this->allowNewAccounts(false);
		$this->discoveryService->expects(self::never())->method('discover');

		$response = $this->controller->discover('user@example.org');

		self::assertInstanceOf(JsonResponse::class, $response);
		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testInvalidAddress(): void {
		$this->allowNewAccounts(true);
		$this->discoveryService->expects(self::never())->method('discover');

		$response = $this->controller->discover('not an address');

		self::assertInstanceOf(JsonResponse::class, $response);
		self::assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testInvalidHost(): void {
		$this->allowNewAccounts(true);
		$this->hostValidator->method('isValid')->with('localhost')->willReturn(false);
		$this->discoveryService->expects(self::never())->method('discover');

		$response = $this->controller->discover('user@localhost');

		self::assertInstanceOf(JsonResponse::class, $response);
		self::assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testStreamsEvents(): void {
		$this->allowNewAccounts(true);
		$this->hostValidator->method('isValid')->with('example.org')->willReturn(true);
		$this->discoveryService->expects(self::once())
			->method('discover')
			->with(self::callback(static fn (Horde_Mail_Rfc822_Address $address): bool => $address->bare_address === 'user@example.org'))
			->willReturnCallback(static function (): Generator {
				yield DiscoveryEvent::probe(MailAccount::PROTOCOL_IMAP, DiscoveryEvent::SOURCE_SRV, DiscoveryEvent::STATUS_RUNNING);
				yield DiscoveryEvent::complete(MailAccount::PROTOCOL_IMAP, false);
			});
		$output = $this->createMock(IOutput::class);
		$chunks = [];
		$output->method('setOutput')
			->willReturnCallback(static function (string $chunk) use (&$chunks): void {
				$chunks[] = $chunk;
			});

		$response = $this->controller->discover('user@example.org');
		self::assertInstanceOf(StreamTraversableResponse::class, $response);
		$response->callback($output);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame('application/x-ndjson', $response->getHeaders()['Content-Type']);
		self::assertSame([
			'{"type":"control","disposition":"start"}' . "\n",
			'{"type":"probe","protocol":"imap","step":"srv","status":"running"}' . "\n",
			'{"type":"complete","protocol":"imap","found":false}' . "\n",
			'{"type":"control","disposition":"end"}' . "\n",
		], $chunks);
	}
}
