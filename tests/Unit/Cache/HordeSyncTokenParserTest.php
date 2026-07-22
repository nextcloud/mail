<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Cache;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Cache\HordeSyncTokenParser;

class HordeSyncTokenParserTest extends TestCase {
	private HordeSyncTokenParser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new HordeSyncTokenParser();
	}

	public function testParseCompleteToken(): void {
		$token = base64_encode('U1234,V5678,H9999');

		$result = $this->parser->parseSyncToken($token);

		$this->assertSame(1234, $result->getNextUid());
		$this->assertSame(5678, $result->getUidValidity());
		$this->assertSame(9999, $result->getHighestModSeq());
	}

	public function testParseTokenWithoutHighestModSeq(): void {
		$token = base64_encode('U1234,V5678');

		$result = $this->parser->parseSyncToken($token);

		$this->assertSame(1234, $result->getNextUid());
		$this->assertSame(5678, $result->getUidValidity());
		$this->assertNull($result->getHighestModSeq());
	}

	public function testParseTokenWithOnlyNextUid(): void {
		$token = base64_encode('U1000');

		$result = $this->parser->parseSyncToken($token);

		$this->assertSame(1000, $result->getNextUid());
		$this->assertNull($result->getUidValidity());
		$this->assertNull($result->getHighestModSeq());
	}

	public function testParseEmptyToken(): void {
		$token = base64_encode('');

		$result = $this->parser->parseSyncToken($token);

		$this->assertNull($result->getNextUid());
		$this->assertNull($result->getUidValidity());
		$this->assertNull($result->getHighestModSeq());
	}
}
