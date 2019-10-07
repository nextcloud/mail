<?php declare(strict_types=1);

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

namespace OCA\Mail\IMAP\Search;

use Horde_Imap_Client_Search_Query;

class SearchFilterStringParser {

	private const FLAG_MAP = [
		'read' => ['SEEN', true],
		'unread' => ['SEEN', false],
		'answered' => ['ANSWERED', true],
	];

	public function parse(?string $filter): Horde_Imap_Client_Search_Query {
		$query = new Horde_Imap_Client_Search_Query();
		if (empty($filter)) {
			return $query;
		}
		$tokens = explode(' ', $filter);
		$textTokens = [];
		foreach ($tokens as $token) {
			if (!$this->parseFilterToken($query, $token)) {
				$textTokens[] = $token;
			}
		}
		if (count($textTokens)) {
			$query->text(implode(' ', $textTokens), false);
		}

		return $query;
	}

	private function parseFilterToken(Horde_Imap_Client_Search_Query $query, $token): bool {
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
					$query->flag($flag[0], $type === 'is' ? $flag[1] : !$flag[1]);
					return true;
				}
				break;
			case 'from':
			case 'to':
			case 'cc':
			case 'bcc':
			case 'subject':
				$query->headerText($type, $param);
				return true;
		}

		return false;
	}

}
