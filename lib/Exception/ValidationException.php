<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
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

namespace OCA\Mail\Exception;

use Exception;
use OCP\AppFramework\Http;

class ValidationException extends Exception {
	/** @var bool[] */
	private $fields;

	public function __construct() {
		$this->fields = [];
	}

	public function getHttpCode(): int {
		return Http::STATUS_BAD_REQUEST;
	}

	public function getFields(): array {
		return $this->fields;
	}

	public function setField(string $key, bool $validates): void {
		$this->fields[$key] = $validates;
	}

	public function setFields(array $fields): void {
		foreach ($fields as $key => $validates) {
			$this->setField($key, $validates);
		}
	}
}
