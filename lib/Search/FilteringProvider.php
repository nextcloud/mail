<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Search;

use DateTimeImmutable;
use OCP\IUser;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use function implode;

class FilteringProvider extends Provider implements IFilteringProvider {

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$filters = [];
		if ($term = $query->getFilter('term')?->get()) {
			if (is_string($term)) {
				$filters[] = "subject:$term";
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
					$filters[] = "from:$email";
					$filters[] = "to:$email";
					$filters[] = "cc:$email";
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

	public function getSupportedFilters(): array {
		return [
			'term',
			'since',
			'until',
			'person',
		];
	}

	public function getAlternateIds(): array {
		return [];
	}

	public function getCustomFilters(): array {
		return [];
	}

}
