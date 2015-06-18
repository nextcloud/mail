<?php

/**
 * ownCloud - Mail app
 *
 * @author Christoph Wurst
 * @copyright 2015 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OCA\Mail\Controller\MessagesController;

/**
 * Tests for lib/controller/messagescontroller
 *
 * @author Christoph Wurst
 */
class messagescontrollertest extends \Test\TestCase {

	private $appName;
	private $request;
	private $accountService;
	private $userId;
	private $userFolder;
	private $contactIntegration;
	private $logger;
	private $l10n;
	private $controller;
	private $account;
	private $mailbox;
	private $message;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService = $this->getMockBuilder('\OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->userId = 'john';
		$this->userFolder = '/tmp/';
		$this->request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->contactIntegration = $this->getMockBuilder('\OCA\Mail\Service\ContactsIntegration')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new MessagesController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->userId,
			$this->userFolder,
			$this->contactIntegration,
			$this->logger,
			$this->l10n);

		$this->account = $this->getMockBuilder('\OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->mailbox = $this->getMockBuilder('\OCA\Mail\Mailbox')
			->disableOriginalConstructor()
			->getMock();
		$this->message = $this->getMockBuilder('\OCA\Mail\Message')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetHtmlBody() {
		$accountId = 17;
		$folderId = 'testfolder';
		$messageId = 4321;

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with($this->equalTo($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getMessage')
			->with($this->equalTo($messageId), $this->equalTo(true))
			->will($this->returnValue($this->message));

		$response = $this->controller->getHtmlBody($accountId, base64_encode($folderId), $messageId);

		$this->assertInstanceOf('\OCA\Mail\Http\HtmlResponse', $response);
		$headers = $response->getHeaders();
		// Check for header existense
		$this->assertTrue(array_key_exists('Cache-Control', $headers));
		$this->assertTrue(array_key_exists('Pragma', $headers));

		// Check header values
		$this->assertSame($headers['Cache-Control'], 'max-age=3600, must-revalidate');
		$this->assertSame($headers['Pragma'], 'cache');
	}

}
