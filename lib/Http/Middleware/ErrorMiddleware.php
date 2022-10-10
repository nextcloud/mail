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

namespace OCA\Mail\Http\Middleware;

use Exception;
use Horde_Imap_Client_Exception;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorMiddleware extends Middleware {
	/** @var LoggerInterface */
	private $logger;

	/** @var IConfig */
	private $config;

	/** @var IControllerMethodReflector */
	private $reflector;

	/**
	 * @param IConfig $config
	 * @param LoggerInterface $logger
	 * @param IControllerMethodReflector $reflector
	 */
	public function __construct(IConfig $config,
								LoggerInterface $logger,
								IControllerMethodReflector $reflector) {
		$this->config = $config;
		$this->logger = $logger;
		$this->reflector = $reflector;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Exception $exception
	 *
	 * @return Response
	 * @throws Exception
	 */
	public function afterException($controller, $methodName, Exception $exception) {
		if (!$this->reflector->hasAnnotation('TrapError')) {
			return parent::afterException($controller, $methodName, $exception);
		}

		if ($exception instanceof ClientException) {
			return JsonResponse::failWith($exception);
		}

		if ($exception instanceof DoesNotExistException) {
			return JSONResponse::fail([], Http::STATUS_NOT_FOUND);
		}

		if ($exception instanceof NotImplemented) {
			return JSONResponse::fail([], Http::STATUS_NOT_IMPLEMENTED);
		}

		$temporary = $this->isTemporaryException($exception);
		if ($temporary) {
			$this->logger->warning($exception->getMessage(), [
				'exception' => $exception,
			]);
		} else {
			$this->logger->error($exception->getMessage(), [
				'exception' => $exception,
			]);
		}
		if ($this->config->getSystemValue('debug', false)) {
			return JsonResponse::errorFromThrowable(
				$exception,
				$temporary ? Http::STATUS_SERVICE_UNAVAILABLE : Http::STATUS_INTERNAL_SERVER_ERROR,
				[
					'debug' => true,
				]
			);
		}

		return JsonResponse::error(
			"Server error",
			$temporary ? Http::STATUS_SERVICE_UNAVAILABLE : Http::STATUS_INTERNAL_SERVER_ERROR
		);
	}

	private function isTemporaryException(Throwable $ex): bool {
		if ($ex instanceof ServiceException && $ex->getPrevious() !== null) {
			$ex = $ex->getPrevious();
		}

		if ($ex instanceof Horde_Imap_Client_Exception) {
			return in_array(
				$ex->getCode(),
				[
					Horde_Imap_Client_Exception::DISCONNECT,
					Horde_Imap_Client_Exception::SERVER_READERROR,
					Horde_Imap_Client_Exception::SERVER_WRITEERROR,
				],
				true
			);
		}

		return false;
	}
}
