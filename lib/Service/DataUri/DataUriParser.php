<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\DataUri;

use OCA\Mail\Exception\InvalidDataUriException;
use function str_contains;

class DataUriParser {
	private const PATTERN = '#^data:(?<media_type>[^,.]*),(?<data>.*)$#';

	/**
	 * @throws InvalidDataUriException
	 */
	public function parse(string $dataUri): DataUri {
		$matches = [];

		if (preg_match(self::PATTERN, $dataUri, $matches) !== 1) {
			throw new InvalidDataUriException();
		}

		if ($matches['media_type'] === '') {
			$items = [];
		} else {
			$items = explode(';', $matches['media_type']);
		}

		$mediaType = 'text/plain';
		$parameters = ['charset' => 'US-ASCII'];
		$base64 = false;

		if (count($items) > 0) {
			$mediaType = array_shift($items);
			foreach ($items as $item) {
				if ($item === 'base64') {
					$base64 = true;
					continue;
				}

				if (str_contains($item, '=')) {
					[$key, $value] = explode('=', $item);
					$parameters[$key] = $value;
				}
			}
		}

		return new DataUri(
			$mediaType,
			$parameters,
			$base64,
			$matches['data']
		);
	}
}
