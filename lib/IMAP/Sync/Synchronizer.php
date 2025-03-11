<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Sync;

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_Sync;
use Horde_Imap_Client_Ids;
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

	private ?string $requestId = null;
	private ?Response $response = null;

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
		bool $hasQresync, // TODO: query client directly, but could be unsafe because login has to happen prior
		int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS): Response {
		// Return cached response from last full sync when QRESYNC is enabled
		if ($hasQresync && $this->response !== null && $request->getId() === $this->requestId) {
			return $this->response;
		}

		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		try {
			// Do a full sync and cache the response when QRESYNC is enabled
			[$newUids, $changedUids, $vanishedUids] = match ($hasQresync) {
				true => $this->doCombinedSync($imapClient, $mailbox, $request),
				false => $this->doSplitSync($imapClient, $mailbox, $request, $criteria),
			};
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

		$this->requestId = $request->getId();
		$this->response = new Response($newMessages, $changedMessages, $vanishedMessageUids, null);
		return $this->response;
	}

	/**
	 * @psalm-return list{int[], int[], int[]} [$newUids, $changedUids, $vanishedUids]
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 */
	private function doCombinedSync(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): array {
		$syncData = $imapClient->sync($mailbox, $request->getToken(), [
			'criteria' => Horde_Imap_Client::SYNC_ALL,
		]);

		return [
			$syncData->newmsgsuids->ids,
			$syncData->flagsuids->ids,
			$syncData->vanisheduids->ids,
		];
	}

	/**
	 * @psalm-return list{int[], int[], int[]} [$newUids, $changedUids, $vanishedUids]
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 */
	private function doSplitSync(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request, int $criteria): array {
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

		return [$newUids, $changedUids, $vanishedUids];
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
		// Without QRESYNC we need to specify the known ids and in oder to avoid
		// overly long IMAP commands they have to be chunked.
		return array_merge(
			[], // for php<7.4 https://www.php.net/manual/en/function.array-merge.php
			...array_map(
				static function (Horde_Imap_Client_Ids $uids) use ($imapClient, $mailbox, $request) {
					return $imapClient->sync($mailbox, $request->getToken(), [
						'criteria' => Horde_Imap_Client::SYNC_FLAGSUIDS,
						'ids' => $uids,
					])->flagsuids->ids;
				},
				chunk_uid_sequence($request->getUids(), self::UID_CHUNK_MAX_BYTES)
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
		// Without QRESYNC we need to specify the known ids and in oder to avoid
		// overly long IMAP commands they have to be chunked.
		return array_merge(
			[], // for php<7.4 https://www.php.net/manual/en/function.array-merge.php
			...array_map(
				static function (Horde_Imap_Client_Ids $uids) use ($imapClient, $mailbox, $request) {
					return $imapClient->sync($mailbox, $request->getToken(), [
						'criteria' => Horde_Imap_Client::SYNC_VANISHEDUIDS,
						'ids' => $uids,
					])->vanisheduids->ids;
				},
				chunk_uid_sequence($request->getUids(), self::UID_CHUNK_MAX_BYTES)
			)
		);
	}
}
