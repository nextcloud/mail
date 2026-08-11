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
use OCA\Mail\Service\HtmlPurify\TransformStyleURLs;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class TransformStyleURLsTest extends TestCase {
	private TransformStyleURLs $transform;
	private IURLGenerator|MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->transform = new TransformStyleURLs($this->urlGenerator);
	}

	public function testNoStyleUnchanged(): void {
		$attr = ['class' => 'container'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testStyleWithoutUrlUnchanged(): void {
		$attr = ['style' => 'color: red; margin: 10px;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testStyleWithHttpUrlBlocked(): void {
		$attr = ['style' => 'background-image: url(http://example.com/image.jpg);'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
		$this->assertStringNotContainsString('http://example.com/image.jpg', $result['style']);
		$this->assertTrue(isset($result['data-original-style']));
		$this->assertSame('background-image: url(http://example.com/image.jpg);', $result['data-original-style']);
	}

	public function testStyleWithHttpsUrlBlocked(): void {
		$attr = ['style' => 'background-image: url(https://example.com/image.jpg);'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
		$this->assertStringNotContainsString('https://example.com/image.jpg', $result['style']);
	}

	public function testMultiplePropertiesSomeWithUrl(): void {
		$attr = ['style' => 'color: blue; background-image: url(http://example.com/bg.png); margin: 5px;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('color: blue', $result['style']);
		$this->assertStringContainsString('margin: 5px', $result['style']);
		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
		$this->assertStringNotContainsString('http://example.com/bg.png', $result['style']);
	}

	public function testDataOriginalStylePreservation(): void {
		$attr = ['style' => 'background: url(http://example.com/img.png) no-repeat;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertTrue(isset($result['data-original-style']));
		$this->assertStringContainsString('http://example.com/img.png', $result['data-original-style']);
		$this->assertSame('background: url(http://example.com/img.png) no-repeat;', $result['data-original-style']);
	}

	public function testUrlWithSingleQuotes(): void {
		$attr = ['style' => "background-image: url('http://example.com/image.jpg');"];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
	}

	public function testUrlWithDoubleQuotes(): void {
		$attr = ['style' => 'background-image: url("http://example.com/image.jpg");'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
	}

	public function testUrlWithoutQuotes(): void {
		$attr = ['style' => 'background-image: url(http://example.com/image.jpg);'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
	}

	public function testRelativeUrlsNotReplaced(): void {
		$attr = ['style' => 'background-image: url(/images/bg.png);'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testEmptyStyleProperty(): void {
		$attr = ['style' => ';;color: red;;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testMultipleUrlsInOneProperty(): void {
		$attr = ['style' => 'background: url(http://example.com/1.png), url(http://example.com/2.png);'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('/apps/mail/img/blocked-image.png', $result['style']);
		$this->assertStringNotContainsString('http://example.com/1.png', $result['style']);
	}
}
