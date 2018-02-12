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

namespace OCA\Mail\Tests\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Controller\ProxyController;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCP\AppFramework\Http\TemplateResponse;

class ProxyControllerTest extends TestCase {

	private $appName;
	private $request;
	private $urlGenerator;
	private $session;
	private $controller;
	private $hostname;
	private $clientService;

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
		$this->clientService = $this->getMockBuilder('\OCP\Http\Client\IClientService')->getMock();
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
			],
			[
				'ftp://example.com',
				true
			],
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
			$this->urlGenerator, $this->session, $this->clientService, $url,
			'example.com');

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
	 * @expectedException Exception
	 */
	public function testRedirectInvalidUrl() {
		$this->controller = new ProxyController($this->appName, $this->request,
			$this->urlGenerator, $this->session, $this->clientService, '', '');
		$this->controller->redirect('ftps://example.com');
	}

	public function testProxy() {
		$src = 'http://example.com';
		$httpResponse = $this->getMockBuilder('\OCP\Http\Client\IResponse')->getMock();
		$content = '🐵🐵🐵';

		$this->session->expects($this->once())
			->method('close');
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')->getMock();
		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));
		$client->expects($this->once())
			->method('get')
			->with($src)
			->will($this->returnValue($httpResponse));
		$httpResponse->expects($this->once())
			->method('getBody')
			->will($this->returnValue($content));

		$expected = new ProxyDownloadResponse($content, $src,
			'application/octet-stream');
		$this->controller = new ProxyController($this->appName, $this->request,
			$this->urlGenerator, $this->session, $this->clientService, '', '');
		$response = $this->controller->proxy($src);

		$this->assertEquals($expected, $response);
	}

}
