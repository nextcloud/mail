<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Db\InternalAddress;

interface IInternalAddressService {
	public function isInternal(string $uid, string $address): bool;

	public function add(string $uid, string $address, string $type, ?bool $trust = true);

	/**
	 * @param string $uid
	 * @return InternalAddress[]
	 */
	public function getInternalAddresses(string $uid): array;
}
