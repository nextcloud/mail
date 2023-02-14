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
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\Exception\UidValidityChangedException;
use OCA\Mail\Exception\MailboxDoesNotSupportModSequencesException;
use OCA\Mail\IMAP\MessageMapper;
use function array_chunk;
use function array_merge;

class Synchronizer {
	/**
	 * This determines how many UIDs we send to IMAP for a check of changed or
	 * vanished messages. The number needs a balance between good performance
	 * (few chunks) and staying below the IMAP command size limits. 15k has
	 * shown to cause IMAP errors for some accounts where the UID list can't be
	 * compressed much by Horde.
	 */
	private const UID_CHUNK_SIZE = 10000;

	/** @var MessageMapper */
	private $messageMapper;

	public function __construct(MessageMapper $messageMapper) {
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $request
	 * @param int $criteria
	 *
	 * @return Response
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 * @throws UidValidityChangedException
	 * @throws MailboxDoesNotSupportModSequencesException
	 */
	public function sync(Horde_Imap_Client_Base $imapClient,
						 Request $request,
						 string $userId,
						 int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS): Response {
		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		try {
			if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
				$newUids = $this->getNewMessageUids($imapClient, $mailbox, $request);
			} else {
				$newUids = [];
			}
			if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
				$changedUids = $this->getChangedMessageUids($imapClient, $mailbox, $request);
			} else {
				$changedUids = [];
			}
			if ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS) {
				$vanishedUids = $this->getVanishedMessageUids($imapClient, $mailbox, $request);
			} else {
				$vanishedUids = [];
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

		$newMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), new Horde_Imap_Client_Ids($newUids), $userId);
		$changedMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), new Horde_Imap_Client_Ids($changedUids), $userId);
		$vanishedMessageUids = $vanishedUids;

		return new Response($newMessages, $changedMessages, $vanishedMessageUids);
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param Request $request
	 *
	 * @return array
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 */
	private function getNewMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): array {
		$newUids = $imapClient->sync($mailbox, $request->getToken(), [
			'criteria' => Horde_Imap_Client::SYNC_NEWMSGSUIDS,
		])->newmsgsuids->ids;
		return $newUids;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param Request $request
	 *
	 * @return array
	 */
	private function getChangedMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): array {
		if ($imapClient->capability->isEnabled('QRESYNC')) {
			return $imapClient->sync($mailbox, $request->getToken(), [
				'criteria' => Horde_Imap_Client::SYNC_FLAGSUIDS,
			])->flagsuids->ids;
		}

		// Without QRESYNC we need to specify the known ids and in oder to avoid
		// overly long IMAP commands they have to be chunked.
		return array_merge(
			[], // for php<7.4 https://www.php.net/manual/en/function.array-merge.php
			...array_map(
				static function (array $uids) use ($imapClient, $mailbox, $request) {
					return $imapClient->sync($mailbox, $request->getToken(), [
						'criteria' => Horde_Imap_Client::SYNC_FLAGSUIDS,
						'ids' => new Horde_Imap_Client_Ids($uids),
					])->flagsuids->ids;
				},
				array_chunk($request->getUids(), self::UID_CHUNK_SIZE)
			)
		);
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param Request $request
	 *
	 * @return array
	 */
	private function getVanishedMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): array {
		if ($imapClient->capability->isEnabled('QRESYNC')) {
			return $imapClient->sync($mailbox, $request->getToken(), [
				'criteria' => Horde_Imap_Client::SYNC_VANISHEDUIDS,
			])->vanisheduids->ids;
		}

		// Without QRESYNC we need to specify the known ids and in oder to avoid
		// overly long IMAP commands they have to be chunked.
		$vanishedUids = array_merge(
			[], // for php<7.4 https://www.php.net/manual/en/function.array-merge.php
			...array_map(
				static function (array $uids) use ($imapClient, $mailbox, $request) {
					return $imapClient->sync($mailbox, $request->getToken(), [
						'criteria' => Horde_Imap_Client::SYNC_VANISHEDUIDS,
						'ids' => new Horde_Imap_Client_Ids($uids),
					])->vanisheduids->ids;
				},
				array_chunk($request->getUids(), self::UID_CHUNK_SIZE)
			)
		);
		return $vanishedUids;
	}
}
