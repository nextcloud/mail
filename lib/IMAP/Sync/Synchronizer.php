<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_Sync;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\Exception\MailboxDoesNotSupportModSequencesException;
use OCA\Mail\Exception\UidValidityChangedException;
use OCA\Mail\IMAP\MessageMapper;
use function array_merge;
use function OCA\Mail\chunk_uid_sequence;

class Synchronizer {
	/**
	 * This determines how many UIDs we send to IMAP for a check of changed or
	 * vanished messages. The number needs a balance between good performance
	 * (few chunks) and staying below the IMAP command size limits. 15k has
	 * shown to cause IMAP errors for some accounts where the UID list can't be
	 * compressed much by Horde.
	 */
	private const UID_CHUNK_MAX_BYTES = 10000;

	/** @var MessageMapper */
	private $messageMapper;

	public function __construct(MessageMapper $messageMapper) {
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $request
	 *
	 * @return Response
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 * @throws UidValidityChangedException
	 * @throws MailboxDoesNotSupportModSequencesException
	 */
	public function sync(Horde_Imap_Client_Base $imapClient,
		Request $request,
		string $userId): Response {
		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		try {
			if ($imapClient->capability->isEnabled('QRESYNC')) {
				$result = $imapClient->sync($mailbox, $request->getToken(), [
					'criteria' => Horde_Imap_Client::SYNC_ALL,
					'ids' => $uids,
				]);

				$newUids = $result->newmsgsuids->ids;
				$changedUids = $result->flagsuids->ids;
				$vanishedUids = $result->vanisheduids->ids;
			} else {
				// Without QRESYNC we need to specify the known ids and in oder to avoid
				// overly long IMAP commands they have to be chunked.

				$newUidChunks = [];
				$changedUidChunks = [];
				$vanishedUidChunks = [];

				foreach (chunk_uid_sequence($request->getUids(), self::UID_CHUNK_MAX_BYTES) as $uidChunk) {
					$result = $imapClient->sync($mailbox, $request->getToken(), [
						'criteria' => Horde_Imap_Client::SYNC_ALL,
						'ids' => $uidChunk,
					]);
					$newUidChunks[] = $result->newmsgsuids->ids;
					$changedUidChunks[] = $result->flagsuids->ids;
					$vanishedUidChunks[] = $result->vanisheduids->ids;
				}

				$newUids = array_merge([], ...$newUidChunks);
				$changedUids = array_merge([], ...$changedUidChunks);
				$vanishedUids = array_merge([], ...$vanishedUidChunks);
			}
		} catch (Horde_Imap_Client_Exception_Sync $e) {
			if ($e->getCode() === Horde_Imap_Client_Exception_Sync::UIDVALIDITY_CHANGED) {
				throw new UidValidityChangedException();
			}
			throw $e;
		} catch (Horde_Imap_Client_Exception $e) {
			if ($e->getCode() === Horde_Imap_Client_Exception::MBOXNOMODSEQ) {
				throw new MailboxDoesNotSupportModSequencesException($e->getMessage(), $e->getCode(), $e);
			}
			throw $e;
		}

		$newMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $newUids, $userId);
		$changedMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $changedUids, $userId);
		$vanishedMessageUids = $vanishedUids;

		return new Response($newMessages, $changedMessages, $vanishedMessageUids);
	}
}
