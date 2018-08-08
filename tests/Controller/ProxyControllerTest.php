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

use ArrayAccess;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Controller\ProxyController;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

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
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->session = $this->createMock(ISession::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->hostname = 'example.com';
	}

	public function redirectDataProvider() {
		return [
			[
				'http://nextcloud.com',
				'http://anotherhostname.com',
				false
			],
			[
				'https://nextcloud.com',
				'http://anotherhostname.com',
				false
			],
			[
				'http://nextcloud.com',
				'https://example.com',
				true
			],
			[
				'http://example.com',
				'https://example.com',
				true
			],
			[
				'https://example.com',
				'https://example.com',
				true
			],
			[
				'ftp://example.com',
				'https://example.com',
				true
			],
		];
	}

	/**
	 * @dataProvider redirectDataProvider
	 */
	public function testRedirect(string $url,
								 string $referrer,
								 bool $authorized) {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('mail.page.index')
			->will($this->returnValue('mail-route'));
		$this->request->server = [
			'HTTP_REFERER' => $referrer,
		];
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			'example.com'
		);
		$expected = new TemplateResponse(
			$this->appName,
			'redirect',
			[
				'authorizedRedirect' => $authorized,
				'url' => $url,
				'urlHost' => parse_url($url, PHP_URL_HOST),
				'mailURL' => 'mail-route'
			],
			'guest'
		);

		$response = $this->controller->redirect($url);

		$this->assertEquals($expected, $response);
	}

	/**
	 * @expectedException Exception
	 */
	public function testRedirectInvalidUrl() {
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			''
		);
		$this->controller->redirect('ftps://example.com');
	}

	public function testProxy() {
		$src = 'http://example.com';
		$httpResponse = $this->createMock(IResponse::class);
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

		$expected = new ProxyDownloadResponse(
			$content,
			$src,
			'application/octet-stream'
		);
		$this->controller = new ProxyController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->session,
			$this->clientService,
			''
		);

		$response = $this->controller->proxy($src);

		$this->assertEquals($expected, $response);
	}

}
