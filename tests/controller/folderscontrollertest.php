<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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
use OC\AppFramework\Http;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Mail\Controller\FoldersController;

class FoldersControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $appName = 'mail';
	private $request;
	private $accountService;
	private $userId = 'john';

	public function setUp() {
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();
		$this->accountService = $this->getMockBuilder('OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new FoldersController($this->appName, $this->request, $this->accountService, $this->userId);
	}

	public function folderDataProvider() {
		$files = [
			'folders_german',
		];
		// Add directory prefix to tests/data
		$data = array_map(function ($file) {
			$path = dirname(__FILE__) . '/../data/' . $file . '.json';
			return [json_decode(file_get_contents($path), true)];
		}, $files);

		// Add empty account = no folders
		array_push($data, [
			[
				'folders' => [
				],
			],
		]);

		return $data;
	}

	/**
	 * @dataProvider folderDataProvider
	 */
	public function testIndex($data) {
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$accountId = 28;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$account->expects($this->once())
			->method('getListArray')
			->will($this->returnValue($data));

		$this->controller->index($accountId);

		//TODO: check result
	}

	public function testShow() {
		$result = $this->controller->show();
		$this->assertEquals(Http::STATUS_NOT_IMPLEMENTED, $result->getStatus());
	}

	public function testUpdate() {
		$result = $this->controller->update();
		$this->assertEquals(Http::STATUS_NOT_IMPLEMENTED, $result->getStatus());
	}

	public function testDestroy() {
		$accountId = 28;
		$folderId = 'my folder';
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$imapConnection = $this->getMockBuilder('Horde_Imap_Client_Socket')
			->disableOriginalConstructor()
			->getMock();
		$account->expects($this->once())
			->method('getImapConnection')
			->will($this->returnValue($imapConnection));
		$imapConnection->expects($this->once())
			->method('deleteMailbox')
			->with($folderId);

		$this->controller->destroy($accountId, $folderId);
	}

	public function testDestroyAccountNotFound() {
		$accountId = 28;
		$folderId = 'my folder';
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->throwException(new DoesNotExistException('folder not found')));

		$response = $this->controller->destroy($accountId, $folderId);
		$expected = new JSONResponse(null, 404);

		$this->assertEquals($expected, $response);
	}

	public function testDestroyFolderNotFound() {
		// TODO: write test
	}

	public function testCreate() {
		$accountId = 13;
		$folderId = 'new folder';
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$imapConnection = $this->getMockBuilder('Horde_Imap_Client_Socket')
			->disableOriginalConstructor()
			->getMock();
		$account->expects($this->once())
			->method('getImapConnection')
			->will($this->returnValue($imapConnection));
		$imapConnection->expects($this->once())
			->method('createMailbox')
			->with($folderId);

		$response = $this->controller->create($accountId, $folderId);

		$expected = new JSONResponse([
			'data' => [
				'id' => $folderId
			]
			], Http::STATUS_CREATED);

		$this->assertEquals($expected, $response);
	}

	public function testCreateWithError() {
		$accountId = 13;
		$folderId = 'new folder';
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$imapConnection = $this->getMockBuilder('Horde_Imap_Client_Socket')
			->disableOriginalConstructor()
			->getMock();
		$account->expects($this->once())
			->method('getImapConnection')
			->will($this->returnValue($imapConnection));
		$imapConnection->expects($this->once())
			->method('createMailbox')
			->with($folderId)
			->will($this->throwException(new \Horde_Imap_Client_Exception()));

		$response = $this->controller->create($accountId, $folderId);

		$expected = new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);

		$this->assertEquals($expected, $response);
	}

	public function testCreateSubFolder() {
		// TODO: write test
	}

	public function testDetectChanges() {
		// TODO: write test
	}

}
