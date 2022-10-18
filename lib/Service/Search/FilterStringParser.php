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
			'unread' => Flag::not(Flag::SEEN),
			'important' => Flag::is(Flag::IMPORTANT),
			'is_important' => FlagExpression::and(
				Flag::is(Flag::IMPORTANT)
			)
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
					$query->addFlagExpression(
						FlagExpression::and(
							Flag::is(Flag::IMPORTANT),
						)
					);

					return true;
				}
				if ($param === 'pi-other') {
					$query->addFlag(
						Flag::not(Flag::IMPORTANT),
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
				// from frontend part subject:My+search+text
				$subject = str_replace('+', ' ', $param);
				$query->addSubject($subject);
				return true;
			case 'tags':
				$tags = explode(',', $param);
				$query->setTags($tags);
				return true;
			case 'start':
				if (!empty($param)) {
					$query->setStart($param);
				}
				return true;
			case 'end':
				if (!empty($param)) {
					$query->setEnd($param);
				}
				return true;
			case 'flags':
				$flagArray = explode(',', $param);
				foreach ($flagArray as $flagItem) {
					if (array_key_exists($flagItem, $flagMap)) {
						/** @var Flag $flag */
						$flag = $flagMap[$flagItem];
						if ($flag instanceof Flag) {
							$query->addFlag($flag);
						} elseif ($flag instanceof FlagExpression) {
							$query->addFlagExpression($flag);
						}
					} elseif ($flagItem === 'attachments') {
						$query->setHasAttachments(true);
					}
				}


				return true;
		}

		return false;
	}
}
