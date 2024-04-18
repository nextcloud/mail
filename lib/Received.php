<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
 *
 * @author 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
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

namespace OCA\Mail;

use JsonSerializable;
use ReturnTypeWillChange;
use SPFLib\Check\Environment;
use SPFLib\Checker;

/**
 * @psalm-immutable
 */
class Received implements JsonSerializable {


	/** @var string */
	private $wrapped;

	private function __construct(string $wrapped) {
		$this->wrapped = $wrapped;
	}


	public static function fromHorde(string $horde): self {
		return new self($horde);
	}

	/**
	 * @return string|null
	 */
	public function getHost(): ?string {
		$pattern_host = '/from\s([a-zA-Z0-9.-]+)\s\(/';
		preg_match($pattern_host, $this->wrapped, $matches_host);
		if(!isset($matches_host[1])) {
			return null;
		}
		return $matches_host[1];
	}

	/**
	 * @return string|null
	 */
	public function getIp(): ?string {
		$pattern_ip = '/\(([^()]+)\)/';
		preg_match($pattern_ip, $this->wrapped, $matches_host);
		if(!isset($matches_host[1])) {
			return null;
		}
		return $matches_host[1];
	}

	public function getSpfCode(string $from): string {
		$checker = new Checker();
		$checkResult = $checker->check(new Environment($this->getIp(), $this->getHost(), $from));

		$code = $checkResult->getCode();
		return $code;
	}

	public function jsonSpfCheck(string $from): array {
		$result = $this->jsonSerialize();
		$result['code'] = $this->getSpfCode($from);
		$result['from'] = $from;
		return $result;
	}


	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'host' => $this->getHost(),
			'ip' => $this->getIp(),
		];
	}

	/**
	 * @param Received $object
	 * @return boolean
	 */
	public function equals($object): bool {
		return $this->getHost() === $object->gethost()
			&& $this->getIp() === $object->getIp();
	}
}
