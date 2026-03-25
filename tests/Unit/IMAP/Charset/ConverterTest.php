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
		// Korean (Outlook) - all ks_c_5601 spellings map to UHC (CP949). Encode
		// with iconv to avoid depending on mbstring's UHC support, and cover the
		// case-insensitive charset spellings.
		$koreanText = '안녕하세요';
		$koreanBytes = iconv('UTF-8', 'CP949', $koreanText);
		$koreanCharsets = ['ks_c_5601-1987', 'ks_c_5601-1989', 'KS_C_5601-1987', 'Ks_C_5601-1987', 'cp949', 'windows-949'];
		$koreanCases = [];
		foreach ($koreanCharsets as $koreanCharset) {
			$koreanMimePart = new Horde_Mime_Part();
			$koreanMimePart->setType('text/plain');
			$koreanMimePart->setCharset($koreanCharset);
			$koreanMimePart->setContents($koreanBytes);
			$koreanCases[] = [$koreanMimePart, $koreanText];
		}
		// Arabic - not in mb
		$windowsMimePart = new Horde_Mime_Part();
		$windowsMimePart->setType('text/plain');
		$windowsMimePart->setCharset('Windows-1256');
		$windowsMimePart->setContents(iconv('UTF-8', 'Windows-1256', 'قام زهاء أوراقهم ما,'));

		return array_merge([
			[$utfMimePart, '😊'],
			[$utfMimeStreamPart, '💦'],
			[$iso88591MimePart, 'Ümlaut'],
			[$iso2022jpMimePart, '外せ園査リツハワ題'],
			[$iso88591MimePart_noCharset, 'בה בדף לחבר ממונרכיה, בקר בגרסה ואמנות דת'],
		], $koreanCases, [
			[$windowsMimePart, 'قام زهاء أوراقهم ما,'],
		]);
	}

	/**
	 * A part without a charset header must still decode via detection.
	 */
	public function testConvertWithNullCharsetFallback(): void {
		$mimePart = $this->createMock(Horde_Mime_Part::class);
		$mimePart->method('getContents')
			->willReturn(mb_convert_encoding('Tëst', 'ISO-8859-1', 'UTF-8'));
		$mimePart->method('getCharset')
			->willReturn(null);

		$result = $this->converter->convert($mimePart);

		$this->assertEquals('Tëst', $result);
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
		$result = null;
		try {
			$result = $this->converter->convert($mimePart);
		} catch (\ValueError $e) {
			$thrown = $e;
		} catch (\OCA\Mail\Exception\ServiceException) {
			// ServiceException is acceptable (auto-detection failed)
		}

		$this->assertNull($thrown, 'ValueError should not bubble up from convert()');
		if ($result !== null) {
			$this->assertTrue(mb_check_encoding($result, 'UTF-8'), 'A successful conversion must yield valid UTF-8');
		}
	}
}
