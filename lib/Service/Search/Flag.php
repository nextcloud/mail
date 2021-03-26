<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use Horde_Imap_Client;
use OCA\Mail\Db\Tag;

/**
 * @psalm-immutable
 */
class Flag {
	public const ANSWERED = Horde_Imap_Client::FLAG_ANSWERED;
	public const SEEN = Horde_Imap_Client::FLAG_SEEN;
	public const FLAGGED = Horde_Imap_Client::FLAG_FLAGGED;
	/** @deprecated */
	public const IMPORTANT = Tag::LABEL_IMPORTANT;
	public const DELETED = Horde_Imap_Client::FLAG_DELETED;

	/** @var string */
	private $flag;

	/** @var bool */
	private $isSet;

	/**
	 * @psalm-param Flag::* $flag
	 */
	private function __construct(string $flag, bool $isSet) {
		$this->flag = $flag;
		$this->isSet = $isSet;
	}

	/**
	 * @psalm-param Flag::* $flag
	 */
	public static function is(string $flag): self {
		return new self($flag, true);
	}

	/**
	 * @psalm-param Flag::* $flag
	 */
	public static function not(string $flag): self {
		return new self($flag, false);
	}

	public function invert(): self {
		return new self($this->flag, !$this->isSet);
	}

	/**
	 * @psalm-return Flag::*
	 */
	public function getFlag(): string {
		return $this->flag;
	}

	public function isSet(): bool {
		return $this->isSet;
	}
}
