<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Search;

use function urldecode;

class FilterStringParser {
	public function parse(?string $filter): SearchQuery {
		$query = new SearchQuery();
		if (empty($filter)) {
			return $query;
		}
		$tokens = explode(' ', $filter);
		foreach ($tokens as $token) {
			$this->parseFilterToken($query, $token);
		}

		return $query;
	}

	private function parseFilterToken(SearchQuery $query, string $token): void {
		if (!str_contains($token, ':')) {
			return;
		}

		[$type, $encodedParam] = explode(':', $token);
		$param = urldecode($encodedParam);
		$type = strtolower($type);
		$flagMap = [
			'answered' => Flag::is(Flag::ANSWERED),
			'read' => Flag::is(Flag::SEEN),
			'unread' => Flag::not(Flag::SEEN),
			'starred' => Flag::is(Flag::FLAGGED),
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
					return;
				}
				if ($param === 'pi-important') {
					$query->addFlagExpression(
						FlagExpression::and(
							Flag::is(Flag::IMPORTANT),
						)
					);

					return;
				}
				if ($param === 'pi-other') {
					$query->addFlag(
						Flag::not(Flag::IMPORTANT),
					);

					return;
				}

				break;
			case 'from':
				$query->addFrom($param);
				return;
			case 'to':
				$query->addTo($param);
				return;
			case 'cc':
				$query->addCc($param);
				return;
			case 'bcc':
				$query->addBcc($param);
				return;
			case 'subject':
				$query->addSubject($param);
				return;
			case 'body':
				$query->addBody($param);
				return;
			case 'tags':
				$tags = explode(',', $param);
				$query->setTags($tags);
				return;
			case 'start':
				if (!empty($param)) {
					$query->setStart($param);
				}
				return;
			case 'end':
				if (!empty($param)) {
					$query->setEnd($param);
				}
				return;
			case 'match':
				$query->setMatch($param);
				return;
			case 'mentions':
				if ($param === 'true') {
					$query->setMentionsMe(true);
				}
				return;
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

				return;
		}
	}
}
