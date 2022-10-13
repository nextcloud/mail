<?php

declare(strict_types=1);

/**
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
