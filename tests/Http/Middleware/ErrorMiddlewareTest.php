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

namespace OCA\Mail\Tests\Http\Middleware;

use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCA\Mail\Service\Logger;
use OCA\Mail\Tests\TestCase;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IConfig;
use PHPUnit_Framework_MockObject_MockObject;

class ErrorMiddlewareTest extends TestCase {

	/** @var IConfig|PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var IControllerMethodReflector|PHPUnit_Framework_MockObject_MockObject */
	private $reflector;

	/** @var ErrorMiddleware */
	private $middleware;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(Logger::class);
		$this->reflector = $this->createMock(IControllerMethodReflector::class);

		$this->middleware = new ErrorMiddleware($this->config, $this->logger,
			$this->reflector);
	}

	public function testDoesNotChangeSuccessfulResponses() {
		$response = new JSONResponse();
		$controller = $this->createMock(Controller::class);

		$after = $this->middleware->afterController($controller, 'index', $response);

		$this->assertSame($response, $after);
	}

	public function testDoesNotChangeUntaggedMethodResponses() {
		$controller = $this->createMock(Controller::class);
		$exception = new DoesNotExistException("nope");
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(false);
		$this->expectException(DoesNotExistException::class);

		$this->middleware->afterException($controller, 'index', $exception);
	}

	public function trappedErrorsData() {
		return [
			[new DoesNotExistException("does not exist"), Http::STATUS_NOT_FOUND],
			[new ServiceException(), Http::STATUS_INTERNAL_SERVER_ERROR],
			[new NotImplemented(), Http::STATUS_NOT_IMPLEMENTED],
		];
	}

	/**
	 * @dataProvider trappedErrorsData
	 */
	public function testTrapsErrors($exception, $expectedStatus) {
		$controller = $this->createMock(Controller::class);
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(true);
		$this->logger->expects($this->once())
			->method('logException')
			->with($exception);

		$response = $this->middleware->afterException($controller, 'index', $exception);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expectedStatus, $response->getStatus());
	}

}
