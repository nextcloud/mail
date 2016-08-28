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
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;
use OCA\Mail\Controller\ProxyController;
use OCA\Mail\Http\ProxyDownloadResponse;

class ProxyControllerTest extends TestCase {

	private $appName;
	private $request;
	private $urlGenerator;
	private $session;
	private $controller;
	private $hostname;
	private $clientService;
	private $client;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMockBuilder('\OCP\ISession')
			->disableOriginalConstructor()
			->getMock();
		$this->clientService = $this->getMock('\OCP\Http\Client\IClientService');
		$this->client = $this->getMock('\OCP\Http\Client\IClient');
		$this->clientService->expects($this->any())
			->method('getClient')
			->will($this->returnValue($this->client));
		$this->hostname = 'example.com';
	}

	public function redirectDataProvider() {
		return [
			[
				'http://nextcloud.com',
				false
			],
			[
				'https://nextcloud.com',
				false
			],
			[
				'http://example.com',
				true
			],
			[
				'https://example.com',
				true
			]
		];
	}

	/**
	 * @dataProvider redirectDataProvider
	 */
	public function testRedirect($url, $authorized) {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.page.index')
			->will($this->returnValue('mail-route'));
		$this->controller = new ProxyController($this->appName, $this->request,
			$this->urlGenerator, $this->session, $this->clientService, $url, 'example.com');

		$expected = new TemplateResponse($this->appName, 'redirect',
			[
			'authorizedRedirect' => $authorized,
			'url' => $url,
			'urlHost' => parse_url($url, PHP_URL_HOST),
			'mailURL' => 'mail-route'
			], 'guest');
		$response = $this->controller->redirect($url);

		$this->assertEquals($expected, $response);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testRedirectInvalidUrl() {
		$this->controller = new ProxyController($this->appName, $this->request,
			$this->urlGenerator, $this->session, $this->clientService, '', '');
		$this->controller->redirect('ftp://example.com');
	}

	public function testProxy() {
		throw new PHPUnit_Framework_SkippedTestError("Test skipped because version hack in ProxyController::getUrlContents is not mockable");

		$src = 'http://example.com';
		$content = '🐵🐵🐵';

		$this->session->expects($this->once())
			->method('close');
		$this->helper->expects($this->once())
			->method('getUrlContent')
			->with($src)
			->will($this->returnValue($content));

		$expected = new ProxyDownloadResponse($content, $src,
			'application/octet-stream');
		$this->controller = new ProxyController($this->appName, $this->request,
			$this->urlGenerator, $this->session, $this->clientService, '', '');
		$response = $this->controller->proxy($src);

		$this->assertEquals($expected, $response);
	}

}
