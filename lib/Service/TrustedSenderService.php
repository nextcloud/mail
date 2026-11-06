<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Db\AddressEntryType;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\TrustedSenderMapper;
use OCA\Mail\Exception\InvalidAddressException;
use OCP\DB\Exception;

class TrustedSenderService implements ITrustedSenderService {
	public function __construct(
		private TrustedSenderMapper $mapper,
		private AddressEntryNormalizer $normalizer,
	) {
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

	/**
	 * @throws InvalidAddressException
	 */
	#[\Override]
	public function trust(string $uid, string $email, string $type, ?bool $trust = true): void {
		if (!$trust) {
			// Valid values are stored normalized, older entries as they were entered
			foreach (array_unique([$email, $this->normalizer->tryNormalize($email, $type) ?? $email]) as $storedEmail) {
				$this->mapper->remove($uid, $storedEmail, $type);
			}
			return;
		}

		$entryType = AddressEntryType::tryFrom($type) ?? throw new InvalidAddressException('Invalid type');
		$email = $this->normalizer->normalize($email, $entryType);
		try {
			$this->mapper->create($uid, $email, $entryType->value);
		} catch (Exception $exception) {
			if ($exception->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $exception;
			}

			// Already listed, e.g. by a concurrent request
		}
	}

	#[\Override]
	public function getTrusted(string $uid): array {
		return $this->mapper->findAll($uid);
	}
}
