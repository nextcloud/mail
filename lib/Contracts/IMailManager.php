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

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\Sync\Request as SyncRequest;
use OCA\Mail\IMAP\Sync\Response as SyncResponse;

interface IMailManager {

	/**
	 * @param Account $account
	 * @return Folder[]
	 */
	public function getFolders(Account $account);

	/**
	 * @param Account
	 * @param SyncRequest $syncRequest
	 * @return SyncResponse
	 */
	public function syncMessages(Account $account, SyncRequest $syncRequest);

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 */
	public function moveMessage(Account $sourceAccount, $sourceFolderId,
		$messageId, Account $destinationAccount, $destFolderId);
}
