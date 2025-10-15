<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http\Middleware;

use Exception;
use Horde_Imap_Client_Exception;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Throwable;

class ErrorMiddleware extends Middleware {
	/** @var LoggerInterface */
	private $logger;

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 * @param LoggerInterface $logger
	 */
	public function __construct(IConfig $config,
		LoggerInterface $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Exception $exception
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[\Override]
	public function afterException($controller, $methodName, Exception $exception) {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$attributes = $reflectionMethod->getAttributes(TrapError::class);
		if ($attributes === []) {
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
			'Server error',
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
