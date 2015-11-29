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
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;
use OCA\Mail\Controller\PageController;

class PageControllerTest extends TestCase {

	private $appName;
	private $request;
	private $userId;
	private $mailAccountMapper;
	private $urlGenerator;
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
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');
		$this->controller = new PageController($this->appName, $this->request,
			$this->mailAccountMapper, $this->urlGenerator, $this->userId);
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

	public function testComposeSimple() {
		$address = 'user@example.com';
		$uri = "mailto:$address";

		$expected = new RedirectResponse('#mailto=' . urlencode($address));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithSubject() {
		$address = 'user@example.com';
		$subject = 'hello there';
		$uri = "mailto:$address?subject=$subject";

		$expected = new RedirectResponse('#mailto=' . urlencode($address)
			. '&subject=' . urlencode($subject));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithCc() {
		$address = 'user@example.com';
		$cc = 'other@example.com';
		$uri = "mailto:$address?cc=$cc";

		$expected = new RedirectResponse('#mailto=' . urlencode($address)
			. '&cc=' . urlencode($cc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithBcc() {
		$address = 'user@example.com';
		$bcc = 'blind@example.com';
		$uri = "mailto:$address?bcc=$bcc";

		$expected = new RedirectResponse('#mailto=' . urlencode($address)
			. '&bcc=' . urlencode($bcc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithMultilineBody() {
		$address = 'user@example.com';
		$body = 'Hi!\nWhat\'s up?\nAnother line';
		$uri = "mailto:$address?body=$body";

		$expected = new RedirectResponse('#mailto=' . urlencode($address)
			. '&body=' . urlencode($body));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

}
