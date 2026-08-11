<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
