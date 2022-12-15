<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\Unit\Http\Middleware;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use Horde_Imap_Client_Exception;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorMiddlewareTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IControllerMethodReflector|MockObject */
	private $reflector;

	/** @var ErrorMiddleware */
	private $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->reflector = $this->createMock(IControllerMethodReflector::class);

		$this->middleware = new ErrorMiddleware(
			$this->config,
			$this->logger,
			$this->reflector
		);
	}

	public function testDoesNotChangeSuccessfulResponses() {
		$response = new JSONResponse();
		$controller = $this->createMock(Controller::class);

		$after = $this->middleware->afterController($controller, 'index', $response);

		$this->assertSame($response, $after);
	}

	public function testDoesNotChangeUntaggedMethodResponses() {
		$controller = $this->createMock(Controller::class);
		$exception = new DoesNotExistException('nope');
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(false);
		$this->expectException(DoesNotExistException::class);

		$this->middleware->afterException($controller, 'index', $exception);
	}

	public function trappedErrorsData() {
		return [
			[new DoesNotExistException('does not exist'), false, Http::STATUS_NOT_FOUND],
			[new ServiceException(), true, Http::STATUS_INTERNAL_SERVER_ERROR],
			[new NotImplemented(), false, Http::STATUS_NOT_IMPLEMENTED],
		];
	}

	/**
	 * @dataProvider trappedErrorsData
	 */
	public function testTrapsErrors($exception, $shouldLog, $expectedStatus) {
		$controller = $this->createMock(Controller::class);
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(true);
		$this->logger->expects($this->exactly($shouldLog ? 1 : 0))
			->method('error')
			->with($exception->getMessage(), [
				'exception' => $exception,
			]);

		$response = $this->middleware->afterException($controller, 'index', $exception);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expectedStatus, $response->getStatus());
	}

	public function testSerializesRecursively() {
		$inner = new Exception();
		$outer = new ServiceException("Test", 0, $inner);
		$controller = $this->createMock(Controller::class);
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(true);
		$this->logger->expects($this->once())
			->method('error')
			->with($outer->getMessage(), [
				'exception' => $outer,
			]);

		$response = $this->middleware->afterException($controller, 'index', $outer);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function temporaryExceptionsData(): array {
		return [
			[new ServiceException('not temporary'), false],
			[new ServiceException('temporary', 0, new Horde_Imap_Client_Exception('', Horde_Imap_Client_Exception::DISCONNECT)), true],
			[new ServiceException('temporary', 0, new Horde_Imap_Client_Exception('', Horde_Imap_Client_Exception::SERVER_CONNECT)), false],
			[new ServiceException('temporary', 0, new Horde_Imap_Client_Exception('', Horde_Imap_Client_Exception::SERVER_READERROR)), true],
			[new ServiceException('temporary', 0, new Horde_Imap_Client_Exception('', Horde_Imap_Client_Exception::SERVER_WRITEERROR)), true],
		];
	}

	/**
	 * @dataProvider temporaryExceptionsData
	 */
	public function testHandlesTemporaryErrors(Throwable $ex, bool $temporary): void {
		$controller = $this->createMock(Controller::class);
		$this->reflector->expects($this->once())
			->method('hasAnnotation')
			->willReturn(true);
		$this->logger->expects($this->once())
			->method($temporary ? 'warning' : 'error')
			->with($ex->getMessage(),
				[
					'exception' => $ex,
				]
			);

		$response = $this->middleware->afterException($controller, 'index', $ex);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(
			$temporary ? Http::STATUS_SERVICE_UNAVAILABLE : Http::STATUS_INTERNAL_SERVER_ERROR,
			$response->getStatus()
		);
	}
}
