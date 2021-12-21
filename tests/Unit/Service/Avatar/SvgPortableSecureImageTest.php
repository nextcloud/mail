<?php

/**
 * @copyright 2021 Gregor Mitzka <gregor.mitzka@gmail.com>
 *
 * @author 2021 Gregor Mitzka <gregor.mitzka@gmail.com>
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
 *
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use Imagick;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\SvgPortableSecureImage;
use OCA\Mail\Exception\ServiceException;

class SvgPortableSecureImageTest extends TestCase {
	public function testValidFile() {
		return $this->assertTrue(
			$this->getValidSvgImage()->isValid()
		);
	}

	public function testInvalidFile() {
		return $this->assertFalse(
			$this->getInvalidSvgImage()->isValid()
		);
	}

	public function testToPngSuccess() {
		$svg = $this->getValidSvgImage();

		$image = $svg->toPngImage();
		$blob1 = $image->getImageBlob();
		$image->clear();

		$blob2 = $this->getValidPngImageAsBlob();

		$this->assertEquals(
			$blob1,
			$blob2
		);
	}

	public function testToPngFailed() {
		$svg = $this->getInvalidSvgImage();

		$this->expectException(
			ServiceException::class
		);

		$svg->toPngImage();
	}

	public function testToPngImageDataUrlSuccess() {
		$svg = $this->getValidSvgImage();

		$url1 = $svg->toPngImageDataUrl();

		$blob = $this->getValidPngImageAsBlob();

		$url2 = sprintf(
			'data:image/png;base64,%s',
			base64_encode($blob)
		);

		$this->assertEquals(
			$url1,
			$url2
		);
	}

	public function testToPngImageDataUrlFailed() {
		$svg = $this->getInvalidSvgImage();

		$this->expectException(
			ServiceException::class
		);

		$svg->toPngImageDataUrl();
	}

	private function getValidPngImage(): Imagick {
		$image = new Imagick();

		$image->readImageBlob(
			$this->getValidSvgImage()->toXml()
		);
		$image->setImageBackgroundColor(
			new ImagickPixel('transparent')
		);

		$image->setImageFormat('png24');
		$image->resizeImage(
			$size, // x
			$size, // y
			Imagick::FILTER_LANCZOS,
			1 // no blur
		);

		return $image;
	}

	private function getValidPngImageAsBlob(): string {
		$image = $this->getValidPngImage();
		$blob = $image->getImageBlob();
		$image->clear();

		return $blob;
	}

	private function getValidSvgImage(): SvgPortableSecureImage {
		$contents = file_get_contents(
			sprintf(
				'%s/valid-svg-ps.svg',
				__DIR__
			)
		);

		return new SvgPortableSecureImage(
			$contents
		);
	}

	private function getInvalidSvgImage(): SvgPortableSecureImage {
		$contents = file_get_contents(
			sprintf(
				'%s/invalid-svg-ps.svg',
				__DIR__
			)
		);

		return new SvgPortableSecureImage(
			$contents
		);
	}
}
