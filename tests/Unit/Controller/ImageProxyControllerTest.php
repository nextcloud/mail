<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\ImageProxyController;
use OCA\Mail\Service\SvgSanitizer;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\LocalServerException;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class ImageProxyControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;

	/** @var IClientService|MockObject */
	private $clientService;

	/** @var IRemoteHostValidator|MockObject */
	private $remoteHostValidator;

	/** @var IMimeTypeDetector|MockObject */
	private $mimeTypeDetector;

	/** @var SvgSanitizer|MockObject */
	private $svgSanitizer;

	/** @var ImageProxyController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->svgSanitizer = $this->createMock(SvgSanitizer::class);
		$this->controller = new ImageProxyController(
			'mail',
			$this->request,
			$this->clientService,
			$this->remoteHostValidator,
			$this->mimeTypeDetector,
			$this->svgSanitizer,
			new NullLogger(),
		);
	}

	public function testFetchRejectsInvalidScheme(): void {
		$this->clientService->expects(self::never())
			->method('newClient');

		$response = $this->controller->fetch('ftp://example.com/image.png');

		self::assertInstanceOf(JSONResponse::class, $response);
		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testFetchReturnsDataUri(): void {
		$content = 'fake-png-bytes';
		$this->remoteHostValidator->method('isValid')
			->willReturn(true);
		$httpResponse = $this->createMock(IResponse::class);
		$httpResponse->method('getBody')
			->willReturn($this->streamFor($content));
		$client = $this->createMock(IClient::class);
		$client->expects(self::once())
			->method('get')
			->willReturn($httpResponse);
		$this->clientService->expects(self::once())
			->method('newClient')
			->willReturn($client);
		$this->mimeTypeDetector->expects(self::once())
			->method('detectString')
			->with($content)
			->willReturn('image/png');

		$response = $this->controller->fetch('https://example.com/image.png');

		self::assertInstanceOf(JSONResponse::class, $response);
		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame(
			['data' => 'data:image/png;base64,' . base64_encode($content)],
			$response->getData(),
		);
	}

	public function testFetchBlocksLocalServer(): void {
		$this->remoteHostValidator->method('isValid')
			->willReturn(true);
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willThrowException(new LocalServerException());
		$this->clientService->method('newClient')
			->willReturn($client);

		$response = $this->controller->fetch('https://localhost/image.png');

		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testFetchBlocksForbiddenHost(): void {
		$this->remoteHostValidator->method('isValid')
			->willReturn(false);
		$this->clientService->expects(self::never())
			->method('newClient');

		$response = $this->controller->fetch('https://127.0.0.1/image.png');

		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testFetchRejectsNonImage(): void {
		$this->remoteHostValidator->method('isValid')
			->willReturn(true);
		$httpResponse = $this->createMock(IResponse::class);
		$httpResponse->method('getBody')
			->willReturn($this->streamFor('not-an-image'));
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturn($httpResponse);
		$this->clientService->method('newClient')
			->willReturn($client);
		$this->mimeTypeDetector->method('detectString')
			->willReturn('text/html');

		$response = $this->controller->fetch('https://example.com/evil');

		self::assertSame(Http::STATUS_UNSUPPORTED_MEDIA_TYPE, $response->getStatus());
	}

	public function testFetchSniffsSvgMisdetectedAsText(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';
		$this->remoteHostValidator->method('isValid')
			->willReturn(true);
		$httpResponse = $this->createMock(IResponse::class);
		$httpResponse->method('getBody')
			->willReturn($this->streamFor($svg));
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturn($httpResponse);
		$this->clientService->method('newClient')
			->willReturn($client);
		$this->mimeTypeDetector->method('detectString')
			->willReturn('text/xml');
		$this->svgSanitizer->method('sanitize')
			->willReturnArgument(0);

		$response = $this->controller->fetch('https://example.com/logo.svg');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame(
			['data' => 'data:image/svg+xml;base64,' . base64_encode($svg)],
			$response->getData(),
		);
	}

	public function testFetchSanitizesSvg(): void {
		$svg = '<svg onload="x()"><rect/></svg>';
		$sanitized = '<svg><rect/></svg>';
		$this->remoteHostValidator->method('isValid')
			->willReturn(true);
		$httpResponse = $this->createMock(IResponse::class);
		$httpResponse->method('getBody')
			->willReturn($this->streamFor($svg));
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturn($httpResponse);
		$this->clientService->method('newClient')
			->willReturn($client);
		$this->mimeTypeDetector->method('detectString')
			->willReturn('image/svg+xml');
		$this->svgSanitizer->expects(self::once())
			->method('sanitize')
			->with($svg)
			->willReturn($sanitized);

		$response = $this->controller->fetch('https://example.com/logo.svg');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame(
			['data' => 'data:image/svg+xml;base64,' . base64_encode($sanitized)],
			$response->getData(),
		);
	}

	/**
	 * @return resource
	 */
	private function streamFor(string $content) {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $content);
		rewind($stream);
		return $stream;
	}
}
