<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
	/** @var Account */
	private $account;

	/** @var AddressList */
	private $to;

	/** @var AddressList */
	private $cc;

	/** @var AddressList */
	private $bcc;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	/** @var array */
	private $attachments;

	/** @var bool */
	private $isHtml;

	/** @var bool */
	private $isMdnRequested;

	/**
	 * @param Account $account
	 * @param AddressList $to
	 * @param AddressList $cc
	 * @param AddressList $bcc
	 * @param string $subject
	 * @param string $body
	 * @param array $attachments
	 * @param bool $isHtml
	 * @param bool $isMdnRequested
	 */
	public function __construct(Account $account,
								AddressList $to,
								AddressList $cc,
								AddressList $bcc,
								string $subject,
								string $body,
								array $attachments = [],
								bool $isHtml = true,
								bool $isMdnRequested = false) {
		$this->account = $account;
		$this->to = $to;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->subject = $subject;
		$this->body = $body;
		$this->attachments = $attachments;
		$this->isHtml = $isHtml;
		$this->isMdnRequested = $isMdnRequested;
	}

	/**
	 * @param Account $account
	 * @param string|null $to
	 * @param string|null $cc
	 * @param string|null $bcc
	 * @param string $subject
	 * @param string $body
	 * @param array $attachments
	 * @param bool $isHtml
	 * @param bool $requestMdn
	 * @return NewMessageData
	 */
	public static function fromRequest(Account $account,
									   string $to = null,
									   string $cc = null,
									   string $bcc = null,
									   string $subject,
									   string $body,
									   array $attachments = [],
									   bool $isHtml = true,
									   bool $requestMdn = false): NewMessageData {
		$toList = AddressList::parse($to ?: '');
		$ccList = AddressList::parse($cc ?: '');
		$bccList = AddressList::parse($bcc ?: '');

		return new self($account, $toList, $ccList, $bccList, $subject, $body, $attachments, $isHtml, $requestMdn);
	}

	/**
	 * @return Account
	 */
	public function getAccount(): Account {
		return $this->account;
	}

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList {
		return $this->to;
	}

	/**
	 * @return AddressList
	 */
	public function getCc(): AddressList {
		return $this->cc;
	}

	/**
	 * @return AddressList
	 */
	public function getBcc(): AddressList {
		return $this->bcc;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * @return string
	 */
	public function getBody(): string {
		return $this->body;
	}

	/**
	 * @return array
	 */
	public function getAttachments(): array {
		return $this->attachments;
	}

	public function isHtml(): bool {
		return $this->isHtml;
	}

	public function isMdnRequested(): bool {
		return $this->isMdnRequested;
	}
}
