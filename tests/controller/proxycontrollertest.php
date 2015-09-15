<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
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
	private $helper;
	private $controller;
	private $hostname;

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
		$this->helper = $this->getMockBuilder('\OCP\IHelper')
			->disableOriginalConstructor()
			->getMock();
		$this->hostname = 'example.com';
	}

	public function redirectDataProvider() {
		return [
			[
				'http://owncloud.org',
				false
			],
			[
				'https://owncloud.org',
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
			$this->urlGenerator, $this->session, $this->helper, $url, 'example.com');

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
			$this->urlGenerator, $this->session, $this->helper, '', '');
		$this->controller->redirect('ftp://example.com');
	}

	public function testProxy() {
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
			$this->urlGenerator, $this->session, $this->helper, '', '');
		$response = $this->controller->proxy($src);

		$this->assertEquals($expected, $response);
	}

}
