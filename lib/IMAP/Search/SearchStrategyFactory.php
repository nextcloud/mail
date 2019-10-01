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
use Horde_Imap_Client_Socket;

class SearchStrategyFactory {

	public function getStrategy(Horde_Imap_Client_Socket $client,
								string $mailbox,
								Horde_Imap_Client_Search_Query $query,
								?int $cursor): ISearchStrategy {
		if (!$client->capability->query('SORT') && 'ALL' === $query->__toString()) {
			return new FullScanSearchStrategy($client, $mailbox, $cursor);
		}

		return new ImapSortSearchStrategy(
			$client,
			$mailbox,
			$query,
			$cursor,
			new FullScanSearchStrategy($client, $mailbox, $cursor)
		);
	}

}
