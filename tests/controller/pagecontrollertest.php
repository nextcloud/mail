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
use OCA\Mail\Controller\PageController;

class PageControllerTest extends TestCase {

	private $appName;
	private $request;
	private $userId;
	private $mailAccountMapper;
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->userId = 'george';
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->mailAccountMapper = $this->getMockBuilder('OCA\Mail\Db\MailAccountMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new PageController($this->appName, $this->request,
			$this->mailAccountMapper, $this->userId);
	}

	public function testIndex() {
		$expected = new TemplateResponse($this->appName, 'index', []);
		// set csp rules for ownCloud 8.1
		if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$csp->addAllowedFrameDomain('\'self\'');
			$expected->setContentSecurityPolicy($csp);
		}

		$response = $this->controller->index();

		$this->assertEquals($expected, $response);
	}

	public function testCompose() {
		$address = 'user@example.com';
		$uri = "mailto:$address";

		$expected = new TemplateResponse($this->appName, 'compose',
			[
			'mailto' => $address,
			'cc' => '',
			'bcc' => '',
			'subject' => '',
			'body' => '',
		]);

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

}
