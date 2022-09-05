<?php

declare(strict_types=1);

/**
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

namespace OCA\Mail\IMAP\Threading;

use JsonSerializable;
use ReturnTypeWillChange;
use function str_replace;
use function strpos;

class Message implements JsonSerializable {
	/** @var string */
	private $subject;

	/** @var string */
	private $id;

	/** @var string[] */
	private $references;

	/**
	 * @param string[] $references
	 */
	public function __construct(string $subject,
								string $id,
								array $references) {
		$this->subject = $subject;
		$this->id = $id;
		$this->references = $references;
	}

	public function hasReSubject(): bool {
		return strpos($this->getSubject(), 'Re:') === 0;
	}

	public function getSubject(): string {
		return $this->subject;
	}

	public function getBaseSubject(): string {
		return str_replace('Re:', '', $this->getSubject());
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string[]
	 */
	public function getReferences(): array {
		return $this->references;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'subject' => $this->subject,
			'id' => $this->id,
			'references' => $this->references,
		];
	}
}
