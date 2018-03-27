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

namespace OCA\Mail\Http\Middleware;

use Exception;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Service\Logger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IConfig;

class ErrorMiddleware extends Middleware {

	/** @var Logger */
	private $logger;

	/** @var IConfig */
	private $config;

	/** @var IControllerMethodReflector */
	private $reflector;

	/**
	 * @param IConfig $config
	 * @param Logger $logger
	 * @param IControllerMethodReflector $reflector
	 */
	public function __construct(IConfig $config, Logger $logger,
		IControllerMethodReflector $reflector) {
		$this->config = $config;
		$this->logger = $logger;
		$this->reflector = $reflector;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Exception $exception
	 * @return JSONResponse
	 */
	public function afterException($controller, $methodName, Exception $exception) {
		if (!$this->reflector->hasAnnotation('TrapError')) {
			return parent::afterException($controller, $methodName, $exception);
		}

		if ($exception instanceof ClientException) {
			return new JSONResponse([
				'message' => $exception->getMessage()
				], Http::STATUS_BAD_REQUEST);
		} else if ($exception instanceof DoesNotExistException) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		} else if ($exception instanceof NotImplemented) {
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		} else {
			$this->logger->logException($exception);
			if ($this->config->getSystemValue('debug', false)) {
				return new JSONResponse([
					'type' => get_class($exception),
					'message' => $exception->getMessage(),
					'code' => $exception->getCode(),
					'trace' => $this->filterTrace($exception->getTrace()),
					], Http::STATUS_INTERNAL_SERVER_ERROR);
			} else {
				return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		}
	}

	private function filterTrace(array $original) {
		return array_map(function(array $row) {
			return array_intersect_key($row,
				array_flip(['file', 'line', 'function', 'class']));
		}, $original);
	}

}
