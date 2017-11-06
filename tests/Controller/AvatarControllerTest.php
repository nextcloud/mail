<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Controller;

use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Controller\AvatarsController;
use OCA\Mail\Http\AvatarDownloadResponse;
use OCA\Mail\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;

class AvatarControllerTest extends TestCase {

	/** @var IAvatarService|PHPUnit_Framework_MockObject_MockObject */
	private $avatarService;

	/** @var AvatarsController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->avatarService = $this->createMock(IAvatarService::class);

		$this->controller = new AvatarsController('mail', $request, $this->avatarService, 'jane');
	}

	public function testGetUrl() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarUrl')
			->with($email, 'jane')
			->willReturn('https://doe.com/favicon.ico');

		$resp = $this->controller->url($email);

		$expected = new JSONResponse(['url' => 'https://doe.com/favicon.ico']);
		$expected->cacheFor(7 * 24 * 60 * 60);
		$this->assertEquals($expected, $resp);
	}

	public function testGetUrlNoAvatarFound() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarUrl')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->url($email);

		$expected = new JSONResponse([], Http::STATUS_NOT_FOUND);
		$expected->cacheFor(24 * 60 * 60);
		$this->assertEquals($expected, $resp);
	}

	public function testGetImage() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn('data');

		$resp = $this->controller->image($email);

		$expected = new AvatarDownloadResponse('data');
		$this->assertEquals($expected, $resp);
	}

	public function testGetImageNotFound() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->image($email);

		$expected = new Response();
		$expected->setStatus(Http::STATUS_NOT_FOUND);
		$expected->cacheFor(0);
		$this->assertEquals($expected, $resp);
	}

}
