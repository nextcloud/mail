<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP\Charset;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Part;
use OCA\Mail\IMAP\Charset\Converter;
use function fopen;
use function fwrite;
use function mb_convert_encoding;

class ConverterTest extends TestCase {

	public Converter $converter;
	protected function setUp(): void {
		parent::setUp();

		$this->converter = new Converter();
	}

	/**
	 * @dataProvider dataProviderMimeParts
	 */
	public function testConvert($mimePart, $expected): void {
		$actual = $this->converter->convert($mimePart);
		$this->assertEquals($expected, $actual);
		$isUtf8 = mb_check_encoding($actual, 'UTF-8');
		$this->assertTrue($isUtf8);
	}

	public function dataProviderMimeParts(): array {
		// UTF8
		$utfMimePart = new Horde_Mime_Part();
		$utfMimePart->setType('text/plain');
		$utfMimePart->setCharset('UTF-8');
		$utfMimePart->setContents('😊');
		// UTF8 stream
		$utfMimeStreamPart = new Horde_Mime_Part();
		$utfMimeStreamPart->setType('text/plain');
		$utfMimeStreamPart->setCharset('UTF-8');
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, '💦');
		$utfMimeStreamPart->setContents($fh, [ 'usestream' => true, ]);
		// Hebrew
		$iso88591MimePart = new Horde_Mime_Part();
		$iso88591MimePart->setType('text/plain');
		$iso88591MimePart->setCharset('ISO-8859-1');
		$iso88591MimePart->setContents(mb_convert_encoding('Ümlaut', 'ISO-8859-1', 'UTF-8'));
		$iso88591MimePart_noCharset = new Horde_Mime_Part();
		$iso88591MimePart_noCharset->setContents('בה בדף לחבר ממונרכיה, בקר בגרסה ואמנות דת');
		// Japanese
		$iso2022jpMimePart = new Horde_Mime_Part();
		$iso2022jpMimePart->setType('text/plain');
		$iso2022jpMimePart->setCharset('ISO-2022-JP');
		$iso2022jpMimePart->setContents(mb_convert_encoding('外せ園査リツハワ題', 'ISO-2022-JP', 'UTF-8'));
		$iso2022jpMimePart_noCharset = new Horde_Mime_Part();
		$iso2022jpMimePart_noCharset->setContents('外せ園査リツハワ題');
		// Korean - not in mb nor iconv
		// $iso106461MimePart = new Horde_Mime_Part();
		// $iso106461MimePart->setType('text/plain');
		// $iso106461MimePart->setCharset('ISO 10646-1');
		//$iso106461MimePart->setContents(iconv('UTF-8', 'ISO 10646-1', '언론·출판은 타인의 명'));
		// Arabic - not in mb
		$windowsMimePart = new Horde_Mime_Part();
		$windowsMimePart->setType('text/plain');
		$windowsMimePart->setCharset('Windows-1256');
		$windowsMimePart->setContents(iconv('UTF-8', 'Windows-1256', 'قام زهاء أوراقهم ما,'));

		return[
			[$utfMimePart, '😊'],
			[$utfMimeStreamPart, '💦'],
			[$iso88591MimePart, 'Ümlaut'],
			[$iso2022jpMimePart, '外せ園査リツハワ題'],
			[$iso88591MimePart_noCharset, 'בה בדף לחבר ממונרכיה, בקר בגרסה ואמנות דת'],
			// [$iso106461MimePart, '언론·출판은 타인의 명'],
			[$windowsMimePart, 'قام زهاء أوراقهم ما,']
		];
	}
}
