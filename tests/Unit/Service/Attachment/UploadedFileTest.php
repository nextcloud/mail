<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Attachment;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Attachment\UploadedFile;

class UploadedFileTest extends TestCase {
	public function testGetFileNameNotExisting() {
		$file = new UploadedFile([]);

		$this->assertEquals(null, $file->getFileName());
	}

	public function testGetFileName() {
		$file = new UploadedFile([
			'name' => 'cat.jpg',
		]);

		$this->assertEquals('cat.jpg', $file->getFileName());
	}

	public function testGetTempPathNotExisting() {
		$file = new UploadedFile([]);

		$this->assertEquals(null, $file->getTempPath());
	}

	public function testGetTempPath() {
		$file = new UploadedFile([
			'tmp_name' => '/tmp/path',
		]);

		$this->assertEquals('/tmp/path', $file->getTempPath());
	}
}
