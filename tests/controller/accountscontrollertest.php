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

use OCA\Mail\Controller\AccountsController;
use OC\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class AccountsControllerTest extends \Test\TestCase {

	private $appName;
	private $request;
	private $accountsService;
	private $userId;
	private $userFolder;
	private $contactsIntegration;
	private $autoConfig;
	private $logger;
	private $l10n;
	private $crypto;
	private $controller;
	private $accountId;
	private $account;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->accountsService = $this->getMockBuilder('\OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->userId = 'manfred';
		$this->userFolder = '/tmp/';
		$this->contactsIntegration = $this->getMockBuilder('OCA\Mail\Service\ContactsIntegration')
			->disableOriginalConstructor()
			->getMock();
		$this->autoConfig = $this->getMockBuilder('\OCA\Mail\Service\AutoConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMockBuilder('\OCP\Security\ICrypto')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new AccountsController($this->appName,
			$this->request,
			$this->accountsService,
			$this->userId,
			$this->userFolder,
			$this->contactsIntegration,
			$this->autoConfig,
			$this->logger,
			$this->l10n,
			$this->crypto);

		$this->account = $this->getMockBuilder('\OCA\Mail\Service\IAccount')
			->disableOriginalConstructor()
			->getMock();
		$this->accountId = 123;
	}

	public function testIndex() {
		$this->account->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue('conf'));
		$this->accountsService->expects($this->once())
			->method('findByUserId')
			->with($this->equalTo($this->userId))
			->will($this->returnValue([$this->account]));

		$response = $this->controller->index();

		$expectedResponse = new JSONResponse(['conf']);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShow() {
		$this->accountsService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue('conf'));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse('conf');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowDoesNotExist() {
		$this->accountsService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getConfiguration')
			->will($this->throwException(new OCP\AppFramework\Db\DoesNotExistException('test123')));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse([]);
		$expectedResponse->setStatus(404);
		$this->assertEquals($expectedResponse, $response);
	}
	
	public function testDestroy() {
		$this->accountsService->expects($this->once())
			->method('delete')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId));

		$response = $this->controller->destroy($this->accountId);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyDoesNotExist() {
		$this->accountsService->expects($this->once())
			->method('delete')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->throwException(new \OCP\AppFramework\Db\DoesNotExistException('test')));

		$response = $this->controller->destroy($this->accountId);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectSuccess() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';

		$this->account->expects($this->exactly(2))
			->method('getId')
			->will($this->returnValue(135));
		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($this->equalTo($email),
				$this->equalTo($password),
				$this->equalTo($accountName))
			->will($this->returnValue($this->account));
		$this->accountsService->expects($this->once())
			->method('save')
			->with($this->equalTo($this->account));

		$response = $this->controller->create($accountName, $email, $password,
			null, null, null, null, null,
			null, null, null, null, null,
			true);

		$expectedResponse = new JSONResponse([
		    'data' => [
			'id' => 135,
		    ],
		], Http::STATUS_CREATED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectFailure() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';

		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($this->equalTo($email),
				$this->equalTo($password),
				$this->equalTo($accountName))
			->will($this->returnValue(null));
		$this->l10n->expects($this->once())
			->method('t')
			->will($this->returnValue('fail'));

		$response = $this->controller->create($accountName, $email, $password,
			null, null, null, null, null,
			null, null, null, null, null,
			true);

		$expectedResponse = new JSONResponse([
		    'message' => 'fail',
		], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testAutoComplete() {
		$this->contactsIntegration->expects($this->once())
			->method('getMatchingRecipient')
			->with($this->equalTo('search term'))
			->will($this->returnValue('test'));

		$response = $this->controller->autoComplete('search term');

		$expectedResponse = 'test';
		$this->assertEquals($expectedResponse, $response);
	}

}
