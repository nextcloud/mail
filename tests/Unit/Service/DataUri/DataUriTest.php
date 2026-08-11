<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\DataUri;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\DataUri\DataUri;

class DataUriTest extends TestCase {
	public function testEntity(): void {
		$mediaType = 'image/png';
		$parameters = ['charset' => 'US-ASCII'];
		$base64 = false;
		$data = 'hello hello';

		$entity = new DataUri(
			$mediaType,
			$parameters,
			$base64,
			$data
		);

		$this->assertEquals($mediaType, $entity->getMediaType());
		$this->assertEquals($parameters, $entity->getParameters());
		$this->assertEquals($base64, $entity->isBase64());
		$this->assertEquals($data, $entity->getData());
	}
}
