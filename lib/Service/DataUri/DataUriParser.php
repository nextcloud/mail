<?php

declare(strict_types=1);

/**
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
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
