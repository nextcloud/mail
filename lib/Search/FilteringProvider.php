<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Search;

use DateTimeImmutable;
use OCP\IUser;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use function implode;
use function urlencode;

class FilteringProvider extends Provider implements IFilteringProvider {

	#[\Override]
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$filters = [];
		if ($term = $query->getFilter('term')?->get()) {
			if (is_string($term)) {
				$filters[] = 'subject:' . urlencode($term);
			}
		}
		if ($since = $query->getFilter('since')?->get()) {
			if ($since instanceof DateTimeImmutable) {
				$ts = $since->getTimestamp();
				$filters[] = "start:$ts";
			}
		}
		if ($until = $query->getFilter('until')?->get()) {
			if ($until instanceof DateTimeImmutable) {
				$ts = $until->getTimestamp();
				$filters[] = "end:$ts";
			}
		}
		if ($userFilter = $query->getFilter('person')?->get()) {
			if ($userFilter instanceof IUser) {
				$email = $userFilter->getEMailAddress();
				if ($email !== null) {
					$filters[] = 'from:' . urlencode($email);
					$filters[] = 'to:' . urlencode($email);
					$filters[] = 'cc:' . urlencode($email);
				}
			}
		}

		if (count($filters) === 0) {
			return SearchResult::complete(
				$this->getName(),
				[]
			);
		}

		return $this->searchByFilter($user, $query, implode(' ', $filters));
	}

	#[\Override]
	public function getSupportedFilters(): array {
		return [
			'term',
			'since',
			'until',
			'person',
		];
	}

	#[\Override]
	public function getAlternateIds(): array {
		return [];
	}

	#[\Override]
	public function getCustomFilters(): array {
		return [];
	}

}
