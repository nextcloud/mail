<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\OauthController;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OauthStateService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class OauthControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private AccountService&MockObject $accountService;
	private OauthStateService&MockObject $oauthStateService;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->oauthStateService = $this->createMock(OauthStateService::class);
	}

	private function buildController(?string $userId): OauthController {
		return new OauthController(
			'mail',
			$this->request,
			$userId,
			$this->accountService,
			$this->oauthStateService,
		);
	}

	public function testGenerateStateSuccess(): void {
		$this->accountService->expects($this->once())
			->method('find')
			->with('alice', 42);

		$this->oauthStateService->expects($this->once())
			->method('createState')
			->with(42, 'alice')
			->willReturn('42.1000.somehmac');

		$response = $this->buildController('alice')->generateState(42);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('42.1000.somehmac', $response->getData()['data']['state']);
	}

	public function testGenerateStateReturnsUnauthorizedWithoutUser(): void {
		$response = $this->buildController(null)->generateState(42);

		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testGenerateStateReturnsNotFoundForUnknownAccount(): void {
		$this->accountService->expects($this->once())
			->method('find')
			->with('alice', 99)
			->willThrowException(new ClientException('not found'));

		$response = $this->buildController('alice')->generateState(99);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}
}
