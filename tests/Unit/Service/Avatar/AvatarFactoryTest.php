<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;

class AvatarFactoryTest extends TestCase {
	private AvatarFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new AvatarFactory();
	}

	public function testCreateInternalReturnsAvatarInstance(): void {
		$url = 'https://internal.example.com/avatar.png';

		$result = $this->factory->createInternal($url);

		$this->assertInstanceOf(Avatar::class, $result);
	}

	public function testCreateInternalWithUrl(): void {
		$url = 'https://internal.example.com/avatar.png';

		$result = $this->factory->createInternal($url);

		$this->assertSame($url, $result->getUrl());
		$this->assertFalse($result->isExternal());
		$this->assertNull($result->getMime());
	}

	public function testCreateExternalReturnsAvatarInstance(): void {
		$url = 'https://external.example.com/avatar.png';
		$mime = 'image/png';

		$result = $this->factory->createExternal($url, $mime);

		$this->assertInstanceOf(Avatar::class, $result);
	}

	public function testCreateExternalWithUrlAndMime(): void {
		$url = 'https://external.example.com/avatar.jpg';
		$mime = 'image/jpeg';

		$result = $this->factory->createExternal($url, $mime);

		$this->assertSame($url, $result->getUrl());
		$this->assertTrue($result->isExternal());
		$this->assertSame($mime, $result->getMime());
	}
}
