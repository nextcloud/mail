<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Search;

use Horde_Imap_Client;
use OCA\Mail\Db\Tag;

/**
 * @psalm-immutable
 */
final class Flag {
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
