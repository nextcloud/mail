<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Controller\ProxyController;
use OCA\Mail\Html\ProxyHmacGenerator;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProxyControllerTest extends TestCase {
	/** @var string */
	private $appName;

	/** @var IRequest|MockObject */
	private $request;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var ISession|MockObject */
	private $session;

	/** @var IClientService|MockObject */
	private $clientService;

	/** @var LoggerInterface */
	private $logger;

	/** @var ProxyHmacGenerator|MockObject */
	private $hmacGenerator;

	/** @var MailManager|MockObject */
	private $mailManager;

	private string $userId = 'user';

	/** @var ProxyController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->hmacGenerator = $this->createMock(ProxyHmacGenerator::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->logger = new NullLogger();
	}

	public function redirectDataProvider() {
		return [
			[
				'http://nextcloud.com',
				false,
				false
			],
			[
				'https://nextcloud.com',
				false,
				false
			],
			[
				'http://nextcloud.com',
				true,
				true
			],
			[
				'http://example.com',
				false,
				false
			],
			[
				'https://example.com',
				true,
				true
			],
			[
				'ftp://example.com',
				true,
				true
			],
		];
	}

	/**
	 * @dataProvider redirectDataProvider
	 */
	public function testRedirect(string $url,
		bool $passesTest,
		bool $authorized) {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.page.index')
			->will($this->returnValue('mail-route'));
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn($passesTest);
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);
		$expected = new TemplateResponse(
			$this->appName,
			'redirect',
			[
				'authorizedRedirect' => $authorized,
				'url' => $url,
				'urlHost' => parse_url($url, PHP_URL_HOST),
				'mailURL' => 'mail-route'
			],
			'guest'
		);

		$response = $this->controller->redirect($url);

		$this->assertEquals($expected, $response);
	}

	public function testRedirectInvalidUrl() {
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);
		$this->expectException(Exception::class);

		$this->controller->redirect('ftps://example.com');
	}

	public function testProxyWithoutCookies(): void {
		$src = 'http://example.com';
		$this->request->expects($this->once())
			->method('passesStrictCookieCheck')
			->willReturn(false);
		$this->session->expects($this->once())
			->method('close');
		$this->clientService->expects(self::never())
			->method('newClient');
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);

		$response = $this->controller->proxy($src, null, null);

		$this->assertInstanceOf(ProxyDownloadResponse::class, $response);
	}

	public function testProxy(): void {
		$src = 'http://example.com';
		$id = 1;
		$validHmac = 'valid-hmac-hash';
		$content = '🐵🐵🐵';
		$httpResponse = $this->createMock(IResponse::class);
		$this->request->expects(self::once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('close');
		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with($id, $src)
			->willReturn($validHmac);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id);
		$client = $this->getMockBuilder(IClient::class)->getMock();
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('get')
			->with($src)
			->willReturn($httpResponse);
		$httpResponse->expects($this->once())
			->method('getBody')
			->willReturn($content);
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);

		$response = $this->controller->proxy($src, $id, $validHmac);

		$this->assertInstanceOf(ProxyDownloadResponse::class, $response);
	}

	public function testProxyWithInvalidHmac(): void {
		$src = 'http://example.com';
		$id = 1;
		$invalidHmac = 'invalid-hmac';
		$expectedHmac = 'expected-hmac';
		$this->request->expects(self::once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('close');
		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with($id, $src)
			->willReturn($expectedHmac);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id);
		$this->clientService->expects(self::never())
			->method('newClient');
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);

		$response = $this->controller->proxy($src, $id, $invalidHmac);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testProxyWithMissingHmacParameters(): void {
		$src = 'http://example.com';
		$this->request->expects(self::once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('close');
		$this->clientService->expects(self::never())
			->method('newClient');
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);

		$response = $this->controller->proxy($src, null, null);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testProxyWithMessageNotOwnedByUser(): void {
		$src = 'http://example.com';
		$id = 1;
		$this->request->expects(self::once())
			->method('passesStrictCookieCheck')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('close');
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willThrowException(new DoesNotExistException('not found'));
		$this->clientService->expects(self::never())
			->method('newClient');
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			$this->hmacGenerator,
			$this->logger,
			$this->mailManager,
			$this->userId,
		);

		$response = $this->controller->proxy($src, $id, 'some-hmac');

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}
}
