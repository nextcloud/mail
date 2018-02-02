<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use OCA\Mail\Folder;
use OCA\Mail\Model\IMAPMessage;

class MessageMapper {

	/**
	 * @param Horde_Imap_Client_Base $client
	 * @param Folder $mailbox
	 * @param array $ids
	 * @return IMAPMessage[]
	 */
	public function findByIds(Horde_Imap_Client_Base $client, $mailbox, array $ids) {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->envelope();
		$query->flags();
		$query->size();
		$query->uid();
		$query->imapDate();
		$query->structure();
		$query->headers('imp',
			[
			'importance',
			'list-post',
			'x-priority',
			'content-type',
			], [
			'cache' => true,
			'peek' => true,
		]);

		$fetchResults = iterator_to_array($client->fetch($mailbox, $query, [
				'ids' => new Horde_Imap_Client_Ids($ids),
			]), false);

		return array_map(function(Horde_Imap_Client_Data_Fetch $fetchResult) use ($client, $mailbox) {
			return new IMAPMessage($client, $mailbox, $fetchResult->getUid(), $fetchResult);
		}, $fetchResults);
	}

	/**
	 * @param Horde_Imap_Client_Base $client
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param string $destFolderId
	 */
	public function move(Horde_Imap_Client_Base $client, $sourceFolderId,
		$messageId, $destFolderId) {
		$client->copy($sourceFolderId, $destFolderId,
			[
			'ids' => new \Horde_Imap_Client_Ids($messageId),
			'move' => true,
		]);
	}

}
