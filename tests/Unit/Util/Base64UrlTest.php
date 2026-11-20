<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Util;

use OCA\Mail\Util\Base64Url;
use PHPUnit\Framework\TestCase;

class Base64UrlTest extends TestCase {
	public function testEncodeRfc4648Vectors(): void {
		self::assertSame('', Base64Url::encode(''));
		self::assertSame('Zg', Base64Url::encode('f'));
		self::assertSame('Zm8', Base64Url::encode('fo'));
		self::assertSame('Zm9v', Base64Url::encode('foo'));
		self::assertSame('Zm9vYg', Base64Url::encode('foob'));
		self::assertSame('Zm9vYmE', Base64Url::encode('fooba'));
		self::assertSame('Zm9vYmFy', Base64Url::encode('foobar'));
	}

	public function testEncodeUrlSafeCharacters(): void {
		// Standard base64 produces + and / for special characters: Pz8+Pg==
		// Base64Url must use - and _ and omit padding =
		self::assertSame('Pz8-Pg', Base64Url::encode('??' . '>>'));

		$messageId = '<org/repo/issues/123/456@example.invalid>';
		self::assertSame('PG9yZy9yZXBvL2lzc3Vlcy8xMjMvNDU2QGV4YW1wbGUuaW52YWxpZD4', Base64Url::encode($messageId));
	}

	public function testDecodeRfc4648Vectors(): void {
		self::assertSame('', Base64Url::decode(''));
		self::assertSame('f', Base64Url::decode('Zg'));
		self::assertSame('fo', Base64Url::decode('Zm8'));
		self::assertSame('foo', Base64Url::decode('Zm9v'));
		self::assertSame('foob', Base64Url::decode('Zm9vYg'));
		self::assertSame('fooba', Base64Url::decode('Zm9vYmE'));
		self::assertSame('foobar', Base64Url::decode('Zm9vYmFy'));
	}

	public function testDecodeUrlSafeCharacters(): void {
		self::assertSame('??>>', Base64Url::decode('Pz8-Pg'));
		self::assertSame('<org/repo/issues/123/456@example.invalid>', Base64Url::decode('PG9yZy9yZXBvL2lzc3Vlcy8xMjMvNDU2QGV4YW1wbGUuaW52YWxpZD4'));
	}

	public function testDecodeInvalid(): void {
		self::assertFalse(Base64Url::decode('invalid base64!'));
	}
}
