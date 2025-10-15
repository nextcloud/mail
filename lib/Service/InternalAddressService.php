<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Contracts\IInternalAddressService;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\InternalAddressMapper;

class InternalAddressService implements IInternalAddressService {
	private InternalAddressMapper $mapper;

	public function __construct(InternalAddressMapper $mapper) {
		$this->mapper = $mapper;
	}

	#[\Override]
	public function isInternal(string $uid, string $address): bool {
		return $this->mapper->exists(
			$uid,
			$address
		);
	}

	#[\Override]
	public function add(string $uid, string $address, string $type, ?bool $trust = true): ?InternalAddress {
		if ($trust && $this->isInternal($uid, $address)) {
			// Nothing to do
			return null;
		}

		if ($trust) {
			$this->mapper->create(
				$uid,
				$address,
				$type
			);
			return $this->getInternalAddress($uid, $address);
		} else {
			$this->mapper->remove(
				$uid,
				$address,
				$type
			);
		}
		return null;
	}

	#[\Override]
	public function getInternalAddresses(string $uid): array {
		return $this->mapper->findAll($uid);
	}

	private function getInternalAddress(string $uid, string $address): ?InternalAddress {
		return $this->mapper->find($uid, $address);
	}

}
