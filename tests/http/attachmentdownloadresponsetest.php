<?php

namespace OCA\Mail\Tests\Http;


use OCA\Mail\Http\AttachmentDownloadResponse;

class AttachmentDownloadResponseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providesResponseData
	 * @param $content
	 * @param $filename
	 * @param $contentType
	 */
	public function testIt($content, $filename, $contentType) {
		$resp = new AttachmentDownloadResponse($content, $filename, $contentType);
		$headers = $resp->getHeaders();
		$this->assertEquals($content, $resp->render());
		$this->assertArrayHasKey('Content-Type', $headers);
		$this->assertEquals($contentType, $headers['Content-Type']);
		$this->assertArrayHasKey('Content-Disposition', $headers);
		$pos = strpos($headers['Content-Disposition'], $filename);
		$this->assertTrue($pos > 0);
    }

	public function providesResponseData() {
		return [
			['1234567890', 'test.txt', 'text/plain']
		];
	}
}
