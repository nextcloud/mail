<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

class SimpleMessage {
	/**
	 * @param string $from
	 * @param string $to
	 * @param string|null $cc
	 * @param string|null $bcc
	 * @param string $date
	 * @param string $subject
	 * @param string $body
	 */
	public function __construct(
		private string $from,
		private string $to,
		private ?string $cc,
		private ?string $bcc,
		private ?string $date,
		private ?string $subject,
		private ?string $body,
	) {
	}

	public function getFrom(): string {
		return $this->from;
	}

	public function getTo(): string {
		return $this->to;
	}

	public function getCc(): ?string {
		return $this->cc;
	}

	public function getBcc(): ?string {
		return $this->bcc;
	}

	public function getDate(): ?string {
		return $this->date;
	}

	public function getSubject(): ?string {
		return $this->subject;
	}

	public function getBody(): ?string {
		return $this->body;
	}
}
