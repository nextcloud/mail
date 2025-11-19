<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

class MessageBuilder {
	private ?string $from = null;

	private ?string $to = null;

	private ?string $cc = null;

	private ?string $bcc = null;

	/** @var string|null */
	private $date;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	public static function create(): static {
		return new static;
	}

	public function from(string $from): static {
		$this->from = $from;
		return $this;
	}

	public function to(string $to): static {
		$this->to = $to;
		return $this;
	}

	public function cc(string $cc): static {
		$this->cc = $cc;
		return $this;
	}

	public function bcc(string $bcc): static {
		$this->bcc = $bcc;
		return $this;
	}

	/**
	 * @param string $date
	 */
	public function date($date): static {
		$this->date = $date;
		return $this;
	}

	/**
	 * @param string $subject
	 */
	public function subject($subject): static {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @param string $body
	 */
	public function body($body): static {
		$this->body = $body;
		return $this;
	}

	public function finish(): \OCA\Mail\Tests\Integration\Framework\SimpleMessage {
		return new SimpleMessage($this->from, $this->to, $this->cc, $this->bcc, $this->date, $this->subject, $this->body);
	}
}
