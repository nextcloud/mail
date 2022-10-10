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
use OCA\Mail\Exception\InvalidDataUriException;
use OCA\Mail\Service\DataUri\DataUri;
use OCA\Mail\Service\DataUri\DataUriParser;

class DataUriParserTest extends TestCase {
	private DataUriParser $parser;

	public function setUp(): void {
		parent::setUp();
		$this->parser = new DataUriParser();
	}

	/**
	 * @dataProvider dataParse
	 */
	public function testParse(string $dataUri, DataUri $expected): void {
		$result = $this->parser->parse($dataUri);

		$this->assertSame($expected->getMediaType(), $result->getMediaType());
		$this->assertSame($expected->getParameters(), $result->getParameters());
		$this->assertSame($expected->isBase64(), $result->isBase64());
		$this->assertSame($expected->getData(), $result->getData());
	}

	public function testParseException(): void {
		$this->expectException(InvalidDataUriException::class);

		$this->parser->parse('hellohello;base64');
	}

	public function dataParse(): array {
		return [
			[
				'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==',
				new DataUri(
					'image/png',
					['charset' => 'US-ASCII'],
					true,
					'iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='
				)
			],
			[
				'data:image/png;charset=TEST,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==',
				new DataUri(
					'image/png',
					['charset' => 'TEST'],
					false,
					'iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='
				)
			],
			[
				'data:text/plain2;charset=UTF-8;page=21,the%20data:1234,5678',
				new DataUri(
					'text/plain2',
					['charset' => 'UTF-8', 'page' => '21'],
					false,
					'the%20data:1234,5678'
				)
			],
			[
				'data:,',
				new DataUri(
					'text/plain',
					['charset' => 'US-ASCII'],
					false,
					''
				)
			]
		];
	}
}
