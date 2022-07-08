<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\AutoConfig;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class ServerConfiguration implements JsonSerializable {
	private string $username;
	private string $host;
	private int $port;
	private string $security;

	public function __construct(string $username,
								string $host,
								int $port,
								string $security) {
		$this->username = $username;
		$this->host = $host;
		$this->port = $port;
		$this->security = $security;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'username' => $this->username,
			'host' => $this->host,
			'port' => $this->port,
			'security' => $this->security,
		];
	}
}
