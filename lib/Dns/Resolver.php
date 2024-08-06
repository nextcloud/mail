<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Dns;

use Pdp\Domain;
use Pdp\Rules;
use function dns_get_record;

class Resolver {

	public function resolve(string $hostname, int $type): array|false {
		return dns_get_record($hostname, $type);
	}

	public function isSuffix(string $hostname): bool {
		$publicSuffixList = Rules::fromPath(__DIR__ . '/../../resources/public_suffix_list.dat');
		$domain = Domain::fromIDNA2008($hostname);

		$result = $publicSuffixList->resolve($domain);

		return $result->secondLevelDomain()->toString() === '';
	}

}
