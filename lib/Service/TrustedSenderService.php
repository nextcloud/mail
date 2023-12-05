<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Db\TrustedSenderMapper;

class TrustedSenderService implements ITrustedSenderService {
	/** @var TrustedSenderMapper */
	private $mapper;

	public function __construct(TrustedSenderMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function isTrusted(string $uid, string $email): bool {
		return $this->mapper->exists(
			$uid,
			$email
		);
	}

	public function trust(string $uid, string $email, string $type, ?bool $trust = true): void {
		if ($trust && $this->isTrusted($uid, $email)) {
			// Nothing to do
			return;
		}

		if ($trust) {
			$this->mapper->create(
				$uid,
				$email,
				$type
			);
		} else {
			$this->mapper->remove(
				$uid,
				$email,
				$type
			);
		}
	}

	public function getTrusted(string $uid): array {
		return $this->mapper->findAll($uid);
	}
}
