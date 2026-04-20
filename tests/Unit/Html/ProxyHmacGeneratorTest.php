<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Html;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Html\ProxyHmacGenerator;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class ProxyHmacGeneratorTest extends TestCase {
	private ProxyHmacGenerator $generator;
	private IConfig|MockObject $config;
	private ICrypto|MockObject $crypto;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->generator = new ProxyHmacGenerator($this->config, $this->crypto);
	}

	public function testGenerateCreatesConsistentHmac(): void {
		$id = 123;
		$src = 'https://example.com/image.png';
		$secret = 'app-secret-key';
		$hmacBinary = 'binary-hmac-data';
		$this->config->expects($this->exactly(2))
			->method('getSystemValueString')
			->with('secret')
			->willReturn($secret);
		$this->crypto->expects($this->exactly(2))
			->method('calculateHMAC')
			->with(
				$src,
				$secret . '|' . $id
			)
			->willReturn($hmacBinary);

		$result1 = $this->generator->generate($id, $src);
		$result2 = $this->generator->generate($id, $src);

		$this->assertSame($result1, $result2);
	}

	public function testGenerateDifferentForDifferentId(): void {
		$src = 'https://example.com/image.png';
		$secret = 'app-secret-key';
		$this->config->expects($this->exactly(2))
			->method('getSystemValueString')
			->with('secret')
			->willReturn($secret);
		$this->crypto->expects($this->exactly(2))
			->method('calculateHMAC')
			->withConsecutive(
				[$src, $secret . '|' . 123],
				[$src, $secret . '|' . 456]
			)
			->willReturnOnConsecutiveCalls('hmac-123', 'hmac-456');

		$result1 = $this->generator->generate(123, $src);
		$result2 = $this->generator->generate(456, $src);

		$this->assertNotSame($result1, $result2);
	}

	public function testGenerateDifferentForDifferentSrc(): void {
		$id = 123;
		$secret = 'app-secret-key';
		$src1 = 'https://example.com/image1.png';
		$src2 = 'https://example.com/image2.png';
		$this->config->expects($this->exactly(2))
			->method('getSystemValueString')
			->with('secret')
			->willReturn($secret);
		$this->crypto->expects($this->exactly(2))
			->method('calculateHMAC')
			->withConsecutive(
				[$src1, $secret . '|' . $id],
				[$src2, $secret . '|' . $id]
			)
			->willReturnOnConsecutiveCalls('hmac-src1', 'hmac-src2');

		$result1 = $this->generator->generate($id, $src1);
		$result2 = $this->generator->generate($id, $src2);

		$this->assertNotSame($result1, $result2);
	}
}
