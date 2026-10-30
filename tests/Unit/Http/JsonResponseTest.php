<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Http;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Http\JsonResponse;

class JsonResponseTest extends TestCase {
	public function testRevalidateWithETagLetsTheBrowserRevalidate(): void {
		$response = JsonResponse::success(['example.test'])->revalidateWithETag();

		$this->assertSame('private, no-cache', $response->getHeaders()['Cache-Control']);
	}

	public function testRevalidateWithETagKeepsTheETagOfUnchangedData(): void {
		$first = JsonResponse::success(['example.test'])->revalidateWithETag();
		$second = JsonResponse::success(['example.test'])->revalidateWithETag();

		$this->assertNotNull($first->getETag());
		$this->assertSame($first->getETag(), $second->getETag());
	}

	public function testRevalidateWithETagChangesTheETagWithTheData(): void {
		$before = JsonResponse::success(['example.test'])->revalidateWithETag();
		$after = JsonResponse::success(['example.test', 'other.test'])->revalidateWithETag();

		$this->assertNotSame($before->getETag(), $after->getETag());
	}
}
