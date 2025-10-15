<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http;

use JsonSerializable;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse as Base;
use Throwable;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_merge;
use function get_class;

/**
 * @see https://github.com/omniti-labs/jsend
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class JsonResponse extends Base {
	/**
	 * @param Http::STATUS_* $statusCode
	 */
	public function __construct($data = [],
		int $statusCode = Http::STATUS_OK) {
		parent::__construct($data, $statusCode);

		$this->addHeader('x-mail-response', 'true');
	}

	/**
	 * @param array|JsonSerializable|bool|string $data
	 * @param Http::STATUS_* $status
	 *
	 * @return static
	 */
	public static function success($data = null,
		int $status = Http::STATUS_OK): self {
		return new self(
			[
				'status' => 'success',
				'data' => $data,
			],
			$status
		);
	}

	/**
	 * @param array|JsonSerializable|bool|string $data
	 * @param Http::STATUS_* $status
	 *
	 * @return static
	 */
	public static function fail($data = null,
		int $status = Http::STATUS_BAD_REQUEST): self {
		return new self(
			[
				'status' => 'fail',
				'data' => $data,
			],
			$status
		);
	}

	public static function failWith(ClientException $exception): self {
		return self::fail(
			[
				'message' => $exception->getMessage(),
				'type' => get_class($exception),
			],
			$exception->getHttpCode()
		);
	}

	/**
	 * @param string $message
	 * @param Http::STATUS_* $status
	 * @param array|JsonSerializable|bool|string $data
	 *
	 * @return static
	 */
	public static function error(string $message,
		int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
		$data = [],
		int $code = 0): self {
		return new self(
			[
				'status' => 'error',
				'message' => $message,
				'data' => $data,
				'code' => $code,
			],
			$status
		);
	}

	/**
	 * @param Throwable $error
	 * @param Http::STATUS_* $status
	 * @param array|JsonSerializable|bool|string $data
	 *
	 * @return static
	 */
	public static function errorFromThrowable(Throwable $error,
		int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
		$data = []): self {
		if (!is_array($data)) {
			$data = [$data];
		}
		return self::error(
			$error->getMessage(),
			$status,
			array_merge(
				$data,
				self::serializeException($error)
			),
			$error->getCode()
		);
	}

	private static function serializeException(?Throwable $throwable): ?array {
		if ($throwable === null) {
			return null;
		}
		return [
			'type' => get_class($throwable),
			'message' => $throwable->getMessage(),
			'code' => $throwable->getCode(),
			'trace' => self::filterTrace($throwable->getTrace()),
			'previous' => self::serializeException($throwable->getPrevious()),
		];
	}

	private static function filterTrace(array $original): array {
		return array_map(static fn (array $row) => array_intersect_key($row,
			array_flip(['file', 'line', 'function', 'class'])), $original);
	}
}
