<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Model;

use OCA\Mail\Account;
use OCA\Mail\AddressList;

/**
 * Simple data class that wraps the request data of a new message or reply
 *
 * @psalm-immutable
 */
class NewMessageData {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly \OCA\Mail\AddressList $to,
		private readonly \OCA\Mail\AddressList $cc,
		private readonly \OCA\Mail\AddressList $bcc,
		private readonly string $subject,
		private readonly string $body,
		private readonly array $attachments = [],
		private readonly bool $isHtml = true,
		private readonly bool $isMdnRequested = false,
		private readonly ?int $smimeCertificateId = null,
		private readonly bool $smimeSign = false,
		private readonly bool $smimeEncrypt = false
	) {
	}

	public static function fromRequest(Account $account,
		string $subject,
		string $body,
		?string $to = null,
		?string $cc = null,
		?string $bcc = null,
		array $attachments = [],
		bool $isHtml = true,
		bool $requestMdn = false,
		?int $smimeCertificateId = null,
		bool $smimeSign = false,
		bool $smimeEncrypt = false): NewMessageData {
		$toList = AddressList::parse($to ?: '');
		$ccList = AddressList::parse($cc ?: '');
		$bccList = AddressList::parse($bcc ?: '');

		return new self(
			$account,
			$toList,
			$ccList,
			$bccList,
			$subject,
			$body,
			$attachments,
			$isHtml,
			$requestMdn,
			$smimeCertificateId,
			$smimeSign,
			$smimeEncrypt,
		);
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getTo(): AddressList {
		return $this->to;
	}

	public function getCc(): AddressList {
		return $this->cc;
	}

	public function getBcc(): AddressList {
		return $this->bcc;
	}

	public function getSubject(): string {
		return $this->subject;
	}

	public function getBody(): string {
		return $this->body;
	}

	public function getAttachments(): array {
		return $this->attachments;
	}

	public function isHtml(): bool {
		return $this->isHtml;
	}

	public function isMdnRequested(): bool {
		return $this->isMdnRequested;
	}

	public function getSmimeSign(): bool {
		return $this->smimeSign;
	}

	public function getSmimeCertificateId(): ?int {
		return $this->smimeCertificateId;
	}

	public function getSmimeEncrypt(): bool {
		return $this->smimeEncrypt;
	}
}
