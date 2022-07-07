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
class Configuration implements JsonSerializable {
	private ?ServerConfiguration $imapConfig;
	private ?ServerConfiguration $smtpConfig;

	public function __construct(?ServerConfiguration $imapConfig,
								?ServerConfiguration $smtpConfig) {
		$this->imapConfig = $imapConfig;
		$this->smtpConfig = $smtpConfig;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'imapConfig' => $this->imapConfig,
			'smtpConfig' => $this->smtpConfig,
		];
	}

	public function getImapConfig(): ?ServerConfiguration {
		return $this->imapConfig;
	}

	public function getSmtpConfig(): ?ServerConfiguration {
		return $this->smtpConfig;
	}
}
