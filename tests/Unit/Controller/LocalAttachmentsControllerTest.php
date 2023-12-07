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

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Controller\LocalAttachmentsController;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;

class LocalAttachmentsControllerTest extends TestCase {
	/** @var IRequest|PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var IAttachmentService|PHPUnit_Framework_MockObject_MockObject */
	private $service;

	/** @var string */
	private $userId;

	/** @var LocalAttachmentsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(IAttachmentService::class);
		$this->userId = 'jane';

		$this->controller = new LocalAttachmentsController('mail', $this->request, $this->service, $this->userId);
	}

	public function testCreateWithoutFile() {
		$this->request->expects($this->once())
			->method('getUploadedFile')
			->with('attachment')
			->willReturn(null);
		$this->expectException(ClientException::class);

		$this->controller->create();
	}

	public function testCreate() {
		$fileData = [
			'name' => 'cat.jpg',
		];
		$this->request->expects($this->once())
			->method('getUploadedFile')
			->with('attachment')
			->willReturn($fileData);
		$attachment = new LocalAttachment();
		$uploadedFile = new UploadedFile($fileData);
		$this->service->expects($this->once())
			->method('addFile')
			->with($this->equalTo($this->userId), $this->equalTo($uploadedFile))
			->willReturn($attachment);

		$actual = $this->controller->create();

		$this->assertEquals(new JSONResponse($attachment, 201), $actual);
	}
}
