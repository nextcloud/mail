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
		// Korean (Outlook) - ks_c_5601-1987 is mapped to UHC (CP949)
		// Use iconv for encoding to avoid dependency on mbstring's UHC support
		$koreanKsc56011987MimePart = new Horde_Mime_Part();
		$koreanKsc56011987MimePart->setType('text/plain');
		$koreanKsc56011987MimePart->setCharset('ks_c_5601-1987');
		$koreanText = '안녕하세요';
		$koreanKsc56011987MimePart->setContents(iconv('UTF-8', 'CP949', $koreanText));
		// Korean (Outlook) - ks_c_5601-1989 is also mapped to UHC (CP949)
		$koreanKsc56011989MimePart = new Horde_Mime_Part();
		$koreanKsc56011989MimePart->setType('text/plain');
		$koreanKsc56011989MimePart->setCharset('ks_c_5601-1989');
		$koreanKsc56011989MimePart->setContents(iconv('UTF-8', 'CP949', $koreanText));
		// Korean (Outlook) - uppercase variant KS_C_5601-1987 (case-insensitive)
		$koreanKsc56011987UpperMimePart = new Horde_Mime_Part();
		$koreanKsc56011987UpperMimePart->setType('text/plain');
		$koreanKsc56011987UpperMimePart->setCharset('KS_C_5601-1987');
		$koreanKsc56011987UpperMimePart->setContents(iconv('UTF-8', 'CP949', $koreanText));
		// Korean (Outlook) - mixed case variant Ks_C_5601-1987 (case-insensitive)
		$koreanKsc56011987MixedMimePart = new Horde_Mime_Part();
		$koreanKsc56011987MixedMimePart->setType('text/plain');
		$koreanKsc56011987MixedMimePart->setCharset('Ks_C_5601-1987');
		$koreanKsc56011987MixedMimePart->setContents(iconv('UTF-8', 'CP949', $koreanText));
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
			[$koreanKsc56011987MimePart, $koreanText],
			[$koreanKsc56011989MimePart, $koreanText],
			[$koreanKsc56011987UpperMimePart, $koreanText],
			[$koreanKsc56011987MixedMimePart, $koreanText],
			[$windowsMimePart, 'قام زهاء أوراقهم ما,']
		];
	}

	/**
	 * Test that conversion succeeds when no charset is specified in the MIME header.
	 *
	 * This tests the code path where $charset is null. The Converter should:
	 * 1. Use mb_detect_encoding() to detect the source encoding
	 * 2. Use the detected charset for conversion to UTF-8
	 *
	 * Without detection, conversion would fail or produce garbled output.
	 */
	public function testConvertWithNullCharsetFallback(): void {
		// Create a mock that returns null for getCharset() to test the null charset path
		$mimePart = $this->createMock(Horde_Mime_Part::class);
		$mimePart->method('getContents')
			->willReturn(mb_convert_encoding('Tëst', 'ISO-8859-1', 'UTF-8'));
		$mimePart->method('getCharset')
			->willReturn(null);

		// Should complete without ValueError and return the correctly converted text
		$result = $this->converter->convert($mimePart);

		// Verify actual conversion correctness, not just UTF-8 validity
		$this->assertEquals('Tëst', $result);
		// Also verify it's valid UTF-8
		$this->assertTrue(mb_check_encoding($result, 'UTF-8'));
	}

	/**
	 * Test that an invalid/unknown charset name does not let ValueError bubble up.
	 *
	 * When an invalid charset is provided, Converter catches the ValueError
	 * and falls back to mbstring auto-detection. The result depends on
	 * mb_detect_order, but the important behavior is that no ValueError escapes.
	 */
	public function testConvertWithInvalidCharsetDoesNotThrowValueError(): void {
		$mimePart = $this->createMock(Horde_Mime_Part::class);
		$mimePart->method('getContents')
			->willReturn(mb_convert_encoding('Tëst with spëcial chärs', 'ISO-8859-1', 'UTF-8'));
		$mimePart->method('getCharset')
			->willReturn('INVALID-CHARSET-NAME-12345');

		$thrown = null;
		try {
			$this->converter->convert($mimePart);
		} catch (\ValueError $e) {
			$thrown = $e;
		} catch (\OCA\Mail\Exception\ServiceException) {
			// ServiceException is acceptable (auto-detection failed)
		}

		$this->assertNull($thrown, 'ValueError should not bubble up from convert()');
	}
}
