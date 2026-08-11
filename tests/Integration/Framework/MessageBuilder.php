<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

class MessageBuilder {
	/** @var string */
	private $from;

	/** @var string */
	private $to;

	/** @var string|null */
	private $cc;

	/** @var string|null */
	private $bcc;

	/** @var string|null */
	private $date;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	/**
	 * @return MessageBuilder
	 */
	public static function create() {
		return new static;
	}

	/**
	 * @param string $from
	 * @return MessageBuilder
	 */
	public function from(string $from) {
		$this->from = $from;
		return $this;
	}

	/**
	 * @param string $to
	 * @return MessageBuilder
	 */
	public function to(string $to) {
		$this->to = $to;
		return $this;
	}

	/**
	 * @param string $cc
	 * @return MessageBuilder
	 */
	public function cc(string $cc) {
		$this->cc = $cc;
		return $this;
	}

	/**
	 * @param string $bcc
	 * @return MessageBuilder
	 */
	public function bcc(string $bcc) {
		$this->bcc = $bcc;
		return $this;
	}

	/**
	 * @param string $date
	 * @return MessageBuilder
	 */
	public function date($date) {
		$this->date = $date;
		return $this;
	}

	/**
	 * @param string $subject
	 * @return MessageBuilder
	 */
	public function subject($subject) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @param string $body
	 * @return MessageBuilder
	 */
	public function body($body) {
		$this->body = $body;
		return $this;
	}

	public function finish() {
		return new SimpleMessage($this->from, $this->to, $this->cc, $this->bcc, $this->date, $this->subject, $this->body);
	}
}
