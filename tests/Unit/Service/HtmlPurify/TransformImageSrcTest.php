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

		$this->assertStringContainsString('display: none;', $result['style']);
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

		$this->assertSame('display: none;', $result['style']);
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
}
