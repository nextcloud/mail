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
use OCA\Mail\Service\HtmlPurify\TransformHTMLLinks;

class TransformHTMLLinksTest extends TestCase {
	private TransformHTMLLinks $transform;

	protected function setUp(): void {
		parent::setUp();
		$this->transform = new TransformHTMLLinks();
	}

	public function testEmptyAttributesUnchanged(): void {
		$attr = [];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertEmpty($result);
	}

	public function testAttributesWithoutHrefUnchanged(): void {
		$attr = ['class' => 'link', 'id' => 'my-link'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame($attr, $result);
	}

	public function testWithHrefAddsTargetAndRel(): void {
		$attr = ['href' => 'https://example.com'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('https://example.com', $result['href']);
		$this->assertSame('_blank', $result['target']);
		$this->assertSame('external noopener noreferrer', $result['rel']);
	}

	public function testExistingTargetOverwritten(): void {
		$attr = ['href' => 'https://example.com', 'target' => '_self'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('_blank', $result['target']);
	}

	public function testExistingRelOverwritten(): void {
		$attr = ['href' => 'https://example.com', 'rel' => 'nofollow'];
		$config = HTMLPurifier_Config::createDefault();
		$context = new HTMLPurifier_Context();

		$result = $this->transform->transform($attr, $config, $context);

		$this->assertSame('external noopener noreferrer', $result['rel']);
	}
}
