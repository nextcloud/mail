<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Service\Attachment;

use OCA\Mail\Service\Attachment\UploadedFile;
use PHPUnit_Framework_TestCase;

class UploadedFileTest extends PHPUnit_Framework_TestCase {

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
