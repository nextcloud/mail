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
use OCA\Mail\Service\HtmlPurify\TransformNoReferrer;

class TransformNoReferrerTest extends TestCase {
	private TransformNoReferrer $transform;

	protected function setUp(): void {
		parent::setUp();
		$this->transform = new TransformNoReferrer();
	}

	public function testNoHrefUnchanged(): void {
		$attr = ['class' => 'link'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testLocalUrlNoNoreferrer(): void {
		$attr = ['href' => '/local/path'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertFalse(isset($result['rel']));
	}

	public function testExternalUrlAddNoreferrer(): void {
		$attr = ['href' => 'https://example.com/page'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('noreferrer', $result['rel']);
	}

	public function testRelAttributeExistsAppendsNoreferrer(): void {
		$attr = ['href' => 'https://example.com', 'rel' => 'nofollow'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('nofollow', $result['rel']);
		$this->assertStringContainsString('noreferrer', $result['rel']);
	}

	public function testRelAttributeDoesNotExistCreatesRel(): void {
		$attr = ['href' => 'https://example.com'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('noreferrer', $result['rel']);
	}

	public function testNoreferrerAlreadyPresentNoDuplication(): void {
		$attr = ['href' => 'https://example.com', 'rel' => 'nofollow noreferrer'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$rels = explode(' ', $result['rel']);
		$noreferrerCount = count(array_filter($rels, fn ($r) => $r === 'noreferrer'));

		$this->assertSame(1, $noreferrerCount);
	}

	public function testMultipleExistingRelsAppendsNoreferrer(): void {
		$attr = ['href' => 'https://example.com', 'rel' => 'nofollow noopener'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertStringContainsString('nofollow', $result['rel']);
		$this->assertStringContainsString('noopener', $result['rel']);
		$this->assertStringContainsString('noreferrer', $result['rel']);
	}
}
