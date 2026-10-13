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
use HTMLPurifier_Token_Start;
use OCA\Mail\Service\HtmlPurify\TransformImageSrc;
use OCP\IURLGenerator;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;

class TransformImageSrcTest extends TestCase {
	private TransformImageSrc $transform;
	private IURLGenerator|MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->transform = new TransformImageSrc($this->urlGenerator);
	}

	public function testNonImgTagsUnchanged(): void {
		$attr = ['src' => 'https://example.com/image.png'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('div');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testNoSrcUnchanged(): void {
		$attr = ['alt' => 'image'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testTrackingPixelReplaced(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'width' => '1', 'height' => '1'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('/apps/mail/img/blocked-image.png', $result['src']);
		$this->assertStringContainsString('display: none', $result['style']);
	}

	public function testTrackingPixelEdgeCaseWidth4Height4(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'width' => '4', 'height' => '4'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('/apps/mail/img/blocked-image.png', $result['src']);
	}

	public function testTrackingPixelNotBlockedWidth5Height5(): void {
		$attr = ['src' => 'https://example.com/image.png', 'width' => '5', 'height' => '5'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('https://example.com/image.png', $result['src']);
		$this->assertFalse(isset($result['style']));
	}

	public function testNonTrackingImagePassesThrough(): void {
		$attr = ['src' => 'https://example.com/image.png', 'width' => '100', 'height' => '100'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('https://example.com/image.png', $result['src']);
		$this->assertFalse(isset($result['style']));
	}

	public function testTrackingPixelStylePreserved(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'width' => '1', 'height' => '1', 'style' => 'margin: 10px;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('display: none !important;', $result['style']);
		$this->assertStringContainsString('margin: 10px;', $result['style']);
	}

	public function testTrackingPixelStyleCreateNew(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'width' => '1', 'height' => '1'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('display: none !important;', $result['style']);
	}

	public function testOnlyHeightSmall(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'height' => '2'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('https://example.com/pixel.png', $result['src']);
		$this->assertFalse(isset($result['style']));
	}

	public function testTrackingPixelStyleNotBackedUp(): void {
		$attr = ['src' => 'https://example.com/pixel.png', 'width' => '1', 'height' => '1', 'style' => 'margin: 10px;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$this->urlGenerator->method('imagePath')->willReturn('/apps/mail/img/blocked-image.png');

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertArrayNotHasKey('data-original-style', $result);
	}

	public function testBlockedImageHidingBeatsOwnDisplay(): void {
		$attr = ['src' => $this->proxySrc(), 'width' => '221', 'style' => 'width:221px;display:block;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('width:221px;display:block; display: none !important;', $result['style']);
	}

	public function testBlockedImageBacksUpOriginalStyle(): void {
		$attr = ['src' => $this->proxySrc(), 'style' => 'width:221px;display:block;'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('width:221px;display:block;', $result['data-original-style']);
	}

	public function testBlockedImageWithoutStyleHasNoBackup(): void {
		$attr = ['src' => $this->proxySrc()];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start('img');
		$context->register('CurrentToken', $token);

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertArrayNotHasKey('data-original-style', $result);
		$this->assertSame('display: none !important;', $result['style']);
	}

	private function proxySrc(): string {
		$this->urlGenerator->method('linkToRoute')
			->with('mail.proxy.proxy')
			->willReturn('/index.php/apps/mail/proxy');
		$this->urlGenerator->method('imagePath')
			->with('mail', 'blocked-image.png')
			->willReturn('/apps/mail/img/blocked-image.png');

		return 'https://' . Util::getServerHostName() . '/index.php/apps/mail/proxy?id=1&src=x';
	}
}
