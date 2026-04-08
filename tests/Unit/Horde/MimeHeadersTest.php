<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Horde;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Headers;
use Horde_Mime_Headers_ContentParam;

/**
 * Ensures that Horde_Mime_Headers and its header element parameters
 * are accessible case-insensitively.
 */
class MimeHeadersTest extends TestCase {
	private Horde_Mime_Headers $headers;

	protected function setUp(): void {
		parent::setUp();

		$raw = "Content-Type: application/pdf; name=\"file.pdf\"\r\n"
			. "Content-Disposition: attachment; filename=\"file.pdf\"\r\n"
			. "Content-Transfer-Encoding: 7bit\r\n"
			. "Content-ID: <abc123@example.com>\r\n";

		$this->headers = Horde_Mime_Headers::parseHeaders($raw);
	}

	public function testHeaderNameAccessIsCaseInsensitive(): void {
		$this->assertSame($this->headers['content-type'], $this->headers['Content-Type']);
		$this->assertSame($this->headers['content-type'], $this->headers['CONTENT-TYPE']);

		$this->assertSame($this->headers['content-disposition'], $this->headers['Content-Disposition']);
		$this->assertSame($this->headers['content-disposition'], $this->headers['CONTENT-DISPOSITION']);

		$this->assertSame($this->headers['content-transfer-encoding'], $this->headers['Content-Transfer-Encoding']);
		$this->assertSame($this->headers['content-transfer-encoding'], $this->headers['CONTENT-TRANSFER-ENCODING']);

		$this->assertSame($this->headers['content-id'], $this->headers['Content-ID']);
		$this->assertSame($this->headers['content-id'], $this->headers['CONTENT-ID']);
	}

	public function testHeaderParameterAccessIsCaseInsensitive(): void {
		$cd = $this->headers['content-disposition'];
		$this->assertInstanceOf(Horde_Mime_Headers_ContentParam::class, $cd);
		$this->assertSame($cd['filename'], $cd['Filename']);
		$this->assertSame($cd['filename'], $cd['FILENAME']);

		$ct = $this->headers['content-type'];
		$this->assertInstanceOf(Horde_Mime_Headers_ContentParam::class, $ct);
		$this->assertSame($ct['name'], $ct['Name']);
		$this->assertSame($ct['name'], $ct['NAME']);
	}
}
