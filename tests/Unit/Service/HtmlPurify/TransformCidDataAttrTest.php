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
use OCA\Mail\Service\HtmlPurify\TransformCidDataAttr;

class TransformCidDataAttrTest extends TestCase {
	private HTMLPurifier_Config $config;

	private array $inlineAttachments = [
		[
			'cid' => 'image001@example.com',
			'url' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7',
		],
		[
			'cid' => 'image002@example.com',
			'url' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/8',
		],
	];

	protected function setUp(): void {
		parent::setUp();
		$this->config = HTMLPurifier_Config::createDefault();
	}

	private function makeContext(string $tagName = 'img'): HTMLPurifier_Context {
		$context = new HTMLPurifier_Context();
		$token = new HTMLPurifier_Token_Start($tagName);
		$context->register('CurrentToken', $token);
		return $context;
	}

	public function testNonImgTagIsUnchanged(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('div');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testImgWithoutSrcIsUnchanged(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = ['alt' => 'image'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testMatchingSrcSetsCidAttribute(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertSame('image001@example.com', $result['data-cid']);
	}

	public function testSecondAttachmentMatches(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/8'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertSame('image002@example.com', $result['data-cid']);
	}

	public function testNonMatchingSrcLeavesNoCidAttribute(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/99'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testPathComparisonIgnoresSchemeAndHost(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		// Same path, different scheme/host (e.g. behind a reverse proxy)
		$attr = ['src' => 'http://internal.proxy/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertSame('image001@example.com', $result['data-cid']);
	}

	public function testAttachmentWithoutCidIsSkipped(): void {
		$attachments = [
			['url' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'],
		];
		$transform = new TransformCidDataAttr($attachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testAttachmentWithoutUrlIsSkipped(): void {
		$attachments = [
			['cid' => 'image001@example.com'],
		];
		$transform = new TransformCidDataAttr($attachments);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testEmptyInlineAttachments(): void {
		$transform = new TransformCidDataAttr([]);
		$attr = ['src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7'];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertArrayNotHasKey('data-cid', $result);
	}

	public function testExistingAttributesArePreserved(): void {
		$transform = new TransformCidDataAttr($this->inlineAttachments);
		$attr = [
			'src' => 'https://mail.example.com/index.php/apps/mail/api/messages/42/attachment/7',
			'alt' => 'inline image',
			'width' => '100',
		];
		$context = $this->makeContext('img');

		$result = $transform->transform($attr, $this->config, $context);

		$this->assertSame('inline image', $result['alt']);
		$this->assertSame('100', $result['width']);
		$this->assertSame('image001@example.com', $result['data-cid']);
	}
}
