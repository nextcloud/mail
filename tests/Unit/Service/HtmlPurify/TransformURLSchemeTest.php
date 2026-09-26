<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\HtmlPurify;

use ChristophWurst\Nextcloud\Testing\TestCase;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use OCA\Mail\Html\ProxyHmacGenerator;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class TransformURLSchemeTest extends TestCase {
	private TransformURLScheme $filter;
	private IURLGenerator|MockObject $urlGenerator;
	private IRequest|MockObject $request;
	private ProxyHmacGenerator|MockObject $hmacGenerator;

	private array $inlineAttachments = [
		['cid' => 'valid-cid', 'url' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/123'],
	];

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->request = $this->createMock(IRequest::class);
		$this->hmacGenerator = $this->createMock(ProxyHmacGenerator::class);

		$this->filter = new TransformURLScheme(
			42,
			$this->inlineAttachments,
			$this->urlGenerator,
			$this->request,
			$this->hmacGenerator,
		);
	}

	public function testNullSchemeDefaultedToHttps(): void {
		$uri = new HTMLPurifier_URI(null, null, 'example.com', null, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('https', $uri->scheme);
	}

	public function testHttpSchemeHandledAsDirectLink(): void {
		$uri = new HTMLPurifier_URI('http', null, 'example.com', null, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('http', $uri->scheme);
		$this->assertSame('example.com', $uri->host);
	}

	public function testHttpsSchemeHandledAsDirectLink(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('https', $uri->scheme);
	}

	public function testFtpSchemeHandled(): void {
		$uri = new HTMLPurifier_URI('ftp', null, 'example.com', null, '/file.zip', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('ftp', $uri->scheme);
	}

	public function testHttpDefaultPortExcluded(): void {
		$uri = new HTMLPurifier_URI('http', null, 'example.com', 80, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
	}

	public function testHttpsDefaultPortExcluded(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', 443, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
	}

	public function testFtpDefaultPortExcluded(): void {
		$uri = new HTMLPurifier_URI('ftp', null, 'example.com', 21, '/file', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
	}

	public function testHttpNonDefaultPortIncluded(): void {
		$uri = new HTMLPurifier_URI('http', null, 'example.com', 8080, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
	}

	public function testHttpsNonDefaultPortIncluded(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', 8443, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
	}

	public function testHrefAttributeUnchanged(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/path', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'href';
		$context->register('CurrentAttr', $attr);

		$originalHost = $uri->host;
		$originalScheme = $uri->scheme;

		$this->filter->filter($uri, $config, $context);

		$this->assertSame($originalScheme, $uri->scheme);
		$this->assertSame($originalHost, $uri->host);
	}

	public function testSrcAttributeProxied(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/image.png', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'src';
		$context->register('CurrentAttr', $attr);

		$originalURL = 'https://example.com/image.png';
		$hmac = 'abc123';

		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with(42, $originalURL)
			->willReturn($hmac);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy', [
				'id' => 42,
				'hmac' => $hmac,
			])
			->willReturn('/apps/mail/proxy?id=42&hmac=abc123');

		$this->filter->filter($uri, $config, $context);

		$this->assertSame('https', $uri->scheme);
		$this->assertSame('mail.example.com', $uri->host);
		$this->assertSame('/apps/mail/proxy', $uri->path);
		$this->assertStringContainsString('id=42', $uri->query);
	}

	public function testCidSchemeWithValidAttachment(): void {
		$uri = new HTMLPurifier_URI('cid', null, null, null, 'valid-cid', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('https', $uri->scheme);
		$this->assertSame('mail.example.com', $uri->host);
	}

	public function testCidSchemeWithInvalidAttachment(): void {
		$uri = new HTMLPurifier_URI('cid', null, null, null, 'invalid-cid', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('cid', $uri->scheme);
	}

	public function testUnsupportedSchemeUnchanged(): void {
		$uri = new HTMLPurifier_URI('mailto', null, null, null, 'test@example.com', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->filter->filter($uri, $config, $context);

		$this->assertTrue($result);
		$this->assertSame('mailto', $uri->scheme);
	}

	public function testUriWithQuery(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/page', 'id=123&name=test', null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'src';
		$context->register('CurrentAttr', $attr);

		$originalURL = 'https://example.com/page?id=123&name=test';
		$hmac = 'abc123';

		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with(42, $originalURL)
			->willReturn($hmac);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy', [
				'id' => 42,
				'hmac' => $hmac,
			])
			->willReturn('/apps/mail/proxy?id=42&hmac=abc123');

		$this->filter->filter($uri, $config, $context);

		$this->assertStringContainsString('id%3D123%26name%3Dtest', $uri->query);
	}

	public function testUriWithFragment(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/page', null, 'section');
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'src';
		$context->register('CurrentAttr', $attr);

		$originalURL = 'https://example.com/page#section';
		$hmac = 'abc123';

		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with(42, $originalURL)
			->willReturn($hmac);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy', [
				'id' => 42,
				'hmac' => $hmac,
			])
			->willReturn('/apps/mail/proxy?id=42&hmac=abc123');

		$this->filter->filter($uri, $config, $context);

		$this->assertStringContainsString('%23section', $uri->query);
	}

	public function testSrcWithPercentEncodedQueryPreservedForHmac(): void {
		$uri = new HTMLPurifier_URI(
			'https',
			null,
			'cdn.example.com',
			null,
			'/img.jpg',
			'sig=%2Fabc%2Bdef%3D&t=123',
			null,
		);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'src';
		$context->register('CurrentAttr', $attr);

		$originalURL = 'https://cdn.example.com/img.jpg?sig=%2Fabc%2Bdef%3D&t=123';
		$hmac = 'deadbeef';

		$this->hmacGenerator->expects($this->once())
			->method('generate')
			->with(42, $originalURL)
			->willReturn($hmac);

		$this->request->method('getServerProtocol')->willReturn('https');
		$this->request->method('getServerHost')->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy', [
				'id' => 42,
				'hmac' => $hmac,
			])
			->willReturnCallback(static function (string $route, array $args): string {
				$query = http_build_query($args, '', '&', PHP_QUERY_RFC3986);
				return '/index.php/apps/mail/proxy?' . strtr($query, [
					'%2F' => '/',
					'%252F' => '%2F',
					'%3F' => '?',
					'%40' => '@',
					'%3A' => ':',
					'%21' => '!',
					'%3B' => ';',
					'%2C' => ',',
					'%2A' => '*',
				]);
			});

		$this->filter->filter($uri, $config, $context);

		parse_str($uri->query, $parsed);
		$this->assertSame(
			$originalURL,
			$parsed['src'],
			'The src reaching the server must be byte-identical to the value the HMAC was generated over',
		);
	}

	public function testCidSchemeRewritesUriFromUrl(): void {
		$uri = new HTMLPurifier_URI('cid', null, null, null, 'valid-cid', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->never())->method('linkToRouteAbsolute');

		$this->filter->filter($uri, $config, $context);

		$this->assertSame('https', $uri->scheme);
		$this->assertSame('mail.example.com', $uri->host);
		$this->assertStringContainsString('/apps/mail/api/messages/42/attachment/123', $uri->path);
	}
}
