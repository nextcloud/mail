<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\TrustedSenderMapper;

class TrustedSenderService implements ITrustedSenderService {
	/** @var TrustedSenderMapper */
	private $mapper;

	public function __construct(TrustedSenderMapper $mapper) {
		$this->mapper = $mapper;
	}

	#[\Override]
	public function isTrusted(string $uid, string $email): bool {
		return $this->mapper->exists(
			$uid,
			$email
		);
	}

	public function isSenderTrusted(string $uid, Message $message): bool {
		$from = $message->getFrom();
		$first = $from->first();
		if ($first === null) {
			return false;
		}
		$email = $first->getEmail();
		if ($email === null) {
			return false;
		}

		return $this->mapper->exists(
			$uid,
			$email
		);
	}

	#[\Override]
	public function trust(string $uid, string $email, string $type, ?bool $trust = true): void {
		if ($trust && $this->isTrusted($uid, $email)) {
			// Nothing to do
			return;
		}

		if ($trust) {
			$this->mapper->create(
				$uid,
				$email,
				$type
			);
		} else {
			$this->mapper->remove(
				$uid,
				$email,
				$type
			);
		}
	}

	#[\Override]
	public function getTrusted(string $uid): array {
		return $this->mapper->findAll($uid);
	}
}
