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

use Horde_Imap_Client;

class FilterStringParser {
	private const FLAG_MAP = [
		'answered' => [Horde_Imap_Client::FLAG_ANSWERED, true],
		'read' => [Horde_Imap_Client::FLAG_SEEN, true],
		'starred' => [Horde_Imap_Client::FLAG_FLAGGED, true],
		'unread' => [Horde_Imap_Client::FLAG_SEEN, false],
		'important' => ['\\important', true],
	];

	public function parse(?string $filter): SearchQuery {
		$query = new SearchQuery();
		if (empty($filter)) {
			return $query;
		}
		$tokens = explode(' ', $filter);
		foreach ($tokens as $token) {
			if (!$this->parseFilterToken($query, $token)) {
				$query->addTextToken($token);
			}
		}

		return $query;
	}

	private function parseFilterToken(SearchQuery $query, string $token): bool {
		if (strpos($token, ':') === false) {
			return false;
		}

		list($type, $param) = explode(':', $token);
		$type = strtolower($type);

		switch ($type) {
			case 'is':
			case 'not':
				if (array_key_exists($param, self::FLAG_MAP)) {
					$flag = self::FLAG_MAP[$param];
					$query->addFlag($flag[0], $type === 'is' ? $flag[1] : !$flag[1]);
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
				$query->setSubject($param);
				return true;
		}

		return false;
	}
}
