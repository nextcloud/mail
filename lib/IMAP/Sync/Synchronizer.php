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

namespace OCA\Mail\IMAP\Sync;

use Horde_Imap_Client_Base;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;

class Synchronizer {

	/** @var ISyncStrategy */
	private $simpleSync;

	/** @var ISyncStrategy */
	private $favSync;

	/**
	 * @param SimpleMailboxSync $simpleSync
	 * @param FavouritesMailboxSync $favSync
	 */
	public function __construct(SimpleMailboxSync $simpleSync,
		FavouritesMailboxSync $favSync) {
		$this->simpleSync = $simpleSync;
		$this->favSync = $favSync;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $request
	 * @return Response
	 */
	public function sync(Horde_Imap_Client_Base $imapClient, Request $request) {
		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		$ids = new Horde_Imap_Client_Ids($request->getUids());
		$hordeSync = $imapClient->sync($mailbox, $request->getToken(), [
			'ids' => $ids
		]);

		$syncStrategy = $this->getSyncStrategy($request);
		$newMessages = $syncStrategy->getNewMessages($imapClient, $request, $hordeSync);
		$changedMessages = $syncStrategy->getChangedMessages($imapClient, $request, $hordeSync);
		$vanishedMessages = $syncStrategy->getVanishedMessages($imapClient, $request, $hordeSync);

		$newSyncToken = $imapClient->getSyncToken($request->getMailbox());
		return new Response($newSyncToken, $newMessages, $changedMessages, $vanishedMessages);
	}

	/**
	 * @param Request $request
	 * @return ISyncStrategy
	 */
	private function getSyncStrategy(Request $request) {
		if ($request->isFlaggedMailbox()) {
			return $this->favSync;
		} else {
			return $this->simpleSync;
		}
	}

}
