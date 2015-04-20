<?php

namespace OCA\Mail\Tests\Http;


use OCA\Mail\Http\HtmlResponse;

class HtmlResponseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providesResponseData
	 * @param $content
	 * @param $filename
	 * @param $contentType
	 */
	public function testIt($content){

		$resp = new HtmlResponse($content);
		$this->assertEquals($content, $resp->render());
    }

	public function providesResponseData() {
		return [
			['1234567890']
		];
	}
}
