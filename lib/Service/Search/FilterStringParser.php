<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Search;

class FilterStringParser {
	public function parse(?string $filter): SearchQuery {
		$query = new SearchQuery();
		if (empty($filter)) {
			return $query;
		}
		$tokens = explode(' ', $filter);
		foreach ($tokens as $token) {
			if (!$this->parseFilterToken($query, $token)) {
				$query->addTextToken($token);

				// Always look into the subject as well
				$query->addSubject($token);
			}
		}

		return $query;
	}

	private function parseFilterToken(SearchQuery $query, string $token): bool {
		if (strpos($token, ':') === false) {
			return false;
		}

		[$type, $param] = explode(':', $token);
		$type = strtolower($type);
		$flagMap = [
			'answered' => Flag::is(Flag::ANSWERED),
			'read' => Flag::is(Flag::SEEN),
			'starred' => Flag::is(Flag::FLAGGED),
			'unread' => Flag::not(Flag::SEEN),
			'important' => Flag::is(Flag::IMPORTANT),
		];

		switch ($type) {
			case 'is':
			case 'not':
				if (array_key_exists($param, $flagMap)) {
					/** @var Flag $flag */
					$flag = $flagMap[$param];
					$query->addFlag($type === 'is' ? $flag : $flag->invert());
					return true;
				}
				if ($param === 'pi-important') {
					// We assume this is about 'is' and not 'not'
					// imp && ~read
					$query->addFlagExpression(
						FlagExpression::and(
							Flag::is(Flag::IMPORTANT),
							Flag::not(Flag::SEEN)
						)
					);

					return true;
				}
				if ($param === 'pi-starred') {
					// We assume this is about 'is' and not 'not'
					// fav /\ (~imp \/ (imp /\ read))
					$query->addFlagExpression(
						FlagExpression::and(
							Flag::is(Flag::FLAGGED),
							FlagExpression::or(
								Flag::not(Flag::IMPORTANT),
								FlagExpression::and(
									Flag::is(Flag::IMPORTANT),
									Flag::is(Flag::SEEN)
								)
							)
						)
					);

					return true;
				}
				if ($param === 'pi-other') {
					// We assume this is about 'is' and not 'not'
					// ~fav && (~imp || (imp && read))
					$query->addFlagExpression(
						FlagExpression::and(
							Flag::not(Flag::FLAGGED),
							FlagExpression::or(
								Flag::not(Flag::IMPORTANT),
								FlagExpression::and(
									Flag::is(Flag::IMPORTANT),
									Flag::is(Flag::SEEN)
								)
							)
						)
					);

					return true;
				}

				break;
			case 'from':
				$query->addFrom($param);
				return true;
			case 'to':
				$query->addTo($param);
				return true;
			case 'cc':
				$query->addCc($param);
				return true;
			case 'bcc':
				$query->addBcc($param);
				return true;
			case 'subject':
				$query->addSubject($param);
				return true;
		}

		return false;
	}
}
