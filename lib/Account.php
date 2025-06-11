<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail;

use JsonSerializable;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Quota;
use ReturnTypeWillChange;

class Account implements JsonSerializable {
	public function __construct(
		private MailAccount $account,
	) {
	}

	public function getMailAccount(): MailAccount {
		return $this->account;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->account->getId();
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->account->getName();
	}

	/**
	 * @return string
	 */
	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->account->toJson();
	}

	public function getEmail(): string {
		return $this->account->getEmail();
	}

	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->account->getUserId();
	}

	/**
	 * @return bool
	 */
	public function getDebug(): bool {
		return $this->account->getDebug();
	}

	/**
	 * Set the quota percentage
	 * @param Quota $quota
	 * @return void
	 */
	public function calculateAndSetQuotaPercentage(Quota $quota): void {
		if ($quota->getLimit() === 0) {
			$this->account->setQuotaPercentage(0);
			return;
		}
		$percentage = (int)round($quota->getUsage() / $quota->getLimit() * 100);
		$this->account->setQuotaPercentage($percentage);
	}

	public function getQuotaPercentage(): ?int {
		return $this->account->getQuotaPercentage();
	}
}
