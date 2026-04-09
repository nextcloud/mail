<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\HtmlPurify;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Closure;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class TransformURLSchemeTest extends TestCase {
	private TransformURLScheme $filter;
	private IURLGenerator|MockObject $urlGenerator;
	private IRequest|MockObject $request;
	private Closure $mapCidToAttachmentId;

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->request = $this->createMock(IRequest::class);

		$this->mapCidToAttachmentId = function (string $cid) {
			if ($cid === 'valid-cid') {
				return 123;
			}
			return null;
		};

		$messageParameters = [
			'accountId' => 1,
			'folderId' => 'INBOX',
			'id' => 42,
		];

		$this->filter = new TransformURLScheme(
			$messageParameters,
			$this->mapCidToAttachmentId,
			$this->urlGenerator,
			$this->request,
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

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy')
			->willReturn('/apps/mail/proxy');

		$this->filter->filter($uri, $config, $context);

		$this->assertSame('https', $uri->scheme);
		$this->assertSame('mail.example.com', $uri->host);
		$this->assertSame('/apps/mail/proxy', $uri->path);
		$this->assertStringContainsString('src=', $uri->query);
	}

	public function testCidSchemeWithValidAttachment(): void {
		$uri = new HTMLPurifier_URI('cid', null, null, null, 'valid-cid', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('mail.messages.downloadAttachment', $this->anything())
			->willReturn('https://mail.example.com/download/123');

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

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy')
			->willReturn('/apps/mail/proxy');

		$this->filter->filter($uri, $config, $context);

		$this->assertStringContainsString('id%3D123%26name%3Dtest', $uri->query);
	}

	public function testUriWithFragment(): void {
		$uri = new HTMLPurifier_URI('https', null, 'example.com', null, '/page', null, 'section');
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$attr = 'src';
		$context->register('CurrentAttr', $attr);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('https');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('mail.example.com');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.proxy.proxy')
			->willReturn('/apps/mail/proxy');

		$this->filter->filter($uri, $config, $context);

		$this->assertStringContainsString('%23section', $uri->query);
	}

	public function testCidSchemeMapsCidToAttachmentId(): void {
		$messageParameters = [
			'accountId' => 1,
			'folderId' => 'INBOX',
			'id' => 42,
		];

		$filter = new TransformURLScheme(
			$messageParameters,
			$this->mapCidToAttachmentId,
			$this->urlGenerator,
			$this->request,
		);

		$uri = new HTMLPurifier_URI('cid', null, null, null, 'valid-cid', null, null);
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('mail.messages.downloadAttachment', [
				'accountId' => 1,
				'folderId' => 'INBOX',
				'id' => 42,
				'attachmentId' => 123,
			])
			->willReturn('https://mail.example.com/download/123');

		$filter->filter($uri, $config, $context);
	}
}
