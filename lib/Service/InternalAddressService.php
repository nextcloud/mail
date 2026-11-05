<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IInternalAddressService;
use OCA\Mail\Db\AddressEntryType;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\InternalAddressMapper;
use OCA\Mail\Exception\InvalidAddressException;
use OCP\DB\Exception;

class InternalAddressService implements IInternalAddressService {
	public function __construct(
		private InternalAddressMapper $mapper,
		private AddressEntryNormalizer $normalizer,
	) {
	}

	/**
	 * @throws InvalidAddressException
	 */
	#[\Override]
	public function add(string $uid, string $address, string $type, ?bool $trust = true): ?InternalAddress {
		if (!$trust) {
			// Valid values are stored normalized, older entries as they were entered
			foreach (array_unique([$address, $this->normalizer->tryNormalize($address, $type) ?? $address]) as $storedAddress) {
				$this->mapper->remove($uid, $storedAddress, $type);
			}
			return null;
		}

		$entryType = AddressEntryType::tryFrom($type) ?? throw new InvalidAddressException('Invalid type');
		$address = $this->normalizer->normalize($address, $entryType);
		try {
			$this->mapper->create($uid, $address, $entryType->value);
		} catch (Exception $exception) {
			if ($exception->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $exception;
			}

			// The address is listed already, e.g. added again from an outdated list or by two requests at once
			return null;
		}
		return $this->getInternalAddress($uid, $address);
	}

	#[\Override]
	public function getInternalAddresses(string $uid): array {
		return $this->mapper->findAll($uid);
	}

	private function getInternalAddress(string $uid, string $address): ?InternalAddress {
		return $this->mapper->find($uid, $address);
	}

}
