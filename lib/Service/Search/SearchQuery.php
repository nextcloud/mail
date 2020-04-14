<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Search;

class SearchQuery {

	/** @var int|null */
	private $cursor;

	/** @var bool[] */
	private $flags = [];

	/** @var string[] */
	private $to = [];

	/** @var string[] */
	private $from = [];

	/** @var string[] */
	private $cc = [];

	/** @var string[] */
	private $bcc = [];

	/** @var string|null */
	private $subject;

	/** @var string[] */
	private $textTokens = [];

	/**
	 * @return int|null
	 */
	public function getCursor(): ?int {
		return $this->cursor;
	}

	/**
	 * @param int $cursor
	 */
	public function setCursor(int $cursor): void {
		$this->cursor = $cursor;
	}

	/**
	 * @return bool[]
	 */
	public function getFlags(): array {
		return $this->flags;
	}

	public function addFlag(string $flag, bool $value = true): void {
		$this->flags[$flag] = $value;
	}

	/**
	 * @return string[]
	 */
	public function getTo(): array {
		return $this->to;
	}

	public function addTo(string $to): void {
		$this->to[] = $to;
	}

	/**
	 * @return string[]
	 */
	public function getFrom(): array {
		return $this->from;
	}

	public function addFrom(string $from): void {
		$this->from[] = $from;
	}

	/**
	 * @return string[]
	 */
	public function getCc(): array {
		return $this->cc;
	}

	public function addCc(string $cc): void {
		$this->cc[] = $cc;
	}

	/**
	 * @return string[]
	 */
	public function getBcc(): array {
		return $this->bcc;
	}

	public function addBcc(string $bcc): void {
		$this->bcc[] = $bcc;
	}

	/**
	 * @return string|null
	 */
	public function getSubject(): ?string {
		return $this->subject;
	}

	public function setSubject(?string $subject): void {
		$this->subject = $subject;
	}

	/**
	 * @return string[]
	 */
	public function getTextTokens(): array {
		return $this->textTokens;
	}

	public function addTextToken(string $textToken): void {
		$this->textTokens[] = $textToken;
	}
}
