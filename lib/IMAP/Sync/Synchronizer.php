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
use Psr\Log\LoggerInterface;
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

	private ?string $requestId = null;
	/** @var list{int[], int[], int[]}|null */
	private ?array $uids = null;

	/**
	 * @psalm-return list{int[], int[], int[]} [$newUids, $changedUids, $vanishedUids]
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 * @throws UidValidityChangedException
	 * @throws MailboxDoesNotSupportModSequencesException
	 */
	public function sync(Horde_Imap_Client_Base $imapClient,
		Request $request,
		bool $hasQresync, // TODO: query client directly, but could be unsafe because login has to happen prior
		LoggerInterface $logger,
		int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS): array {
		// Return cached response from last full sync when QRESYNC is enabled
		if ($hasQresync && $this->uids !== null && $request->getId() === $this->requestId) {
			$logger->debug('Reusing cached sync response');
			return $this->uids;
		}

		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		try {
			// Do a full sync and cache the response when QRESYNC is enabled
			[$newUids, $changedUids, $vanishedUids] = match ($hasQresync) {
				true => $this->doCombinedSync($imapClient, $mailbox, $request, $logger),
				false => $this->doSplitSync($imapClient, $mailbox, $request, $criteria, $logger),
			};
		} catch (Horde_Imap_Client_Exception_Sync $e) {
			$logger->info('UID validity changed');
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

		$nNew = count($newUids);
		$logger->debug("Found {$nNew} new messages");
		$nChanged = count($changedUids);
		$logger->debug("Found {$nChanged} changed messages");

		$this->requestId = $request->getId();
		$this->uids = [$newUids, $changedUids, $vanishedUids];
		return $this->uids;
	}

	/**
	 * @psalm-return list{int[], int[], int[]} [$newUids, $changedUids, $vanishedUids]
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 */
	private function doCombinedSync(Horde_Imap_Client_Base $imapClient,
		Horde_Imap_Client_Mailbox $mailbox,
		Request $request,
		LoggerInterface $logger): array {
		$logger->debug('Performing a combined sync');

		$syncData = $imapClient->sync($mailbox, $request->getToken(), [
			'criteria' => Horde_Imap_Client::SYNC_ALL,
		]);

		$logger->debug('Combined sync finished');

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
	private function doSplitSync(Horde_Imap_Client_Base $imapClient,
		Horde_Imap_Client_Mailbox $mailbox,
		Request $request,
		int $criteria,
		LoggerInterface $logger): array {
		$logger->debug('Performing a split sync');

		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			$newUids = $this->getNewMessageUids($imapClient, $mailbox, $request)->ids;
		} else {
			$newUids = [];
		}
		if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
			$changedUids = $this->getChangedMessageUids($imapClient, $mailbox, $request)->ids;
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
	 * @return Horde_Imap_Client_Ids
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 */
	private function getNewMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): Horde_Imap_Client_Ids {
		return $imapClient->sync($mailbox, $request->getToken(), [
			'criteria' => Horde_Imap_Client::SYNC_NEWMSGSUIDS,
		])->newmsgsuids;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param Request $request
	 *
	 * @return Horde_Imap_Client_Ids
	 */
	private function getChangedMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): Horde_Imap_Client_Ids {
		// Without QRESYNC we need to specify the known ids and in order to avoid
		// overly long IMAP commands they have to be chunked.
		$combined = new Horde_Imap_Client_Ids();
		foreach (chunk_uid_sequence($request->getUids(), self::UID_CHUNK_MAX_BYTES) as $chunk) {
			$syncResult = $imapClient->sync($mailbox, $request->getToken(), [
				'criteria' => Horde_Imap_Client::SYNC_FLAGSUIDS,
				'ids' => $chunk,
			]);
			$combined->add($syncResult->flagsuids);
		}
		return $combined;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param Request $request
	 *
	 * @return array
	 */
	private function getVanishedMessageUids(Horde_Imap_Client_Base $imapClient, Horde_Imap_Client_Mailbox $mailbox, Request $request): array {
		// Without QRESYNC we need to specify the known ids and in order to avoid
		// overly long IMAP commands they have to be chunked.
		return array_merge(
			[], // for php<7.4 https://www.php.net/manual/en/function.array-merge.php
			...array_map(
				static fn (Horde_Imap_Client_Ids $uids) => $imapClient->sync($mailbox, $request->getToken(), [
					'criteria' => Horde_Imap_Client::SYNC_VANISHEDUIDS,
					'ids' => $uids,
				])->vanisheduids->ids,
				chunk_uid_sequence($request->getUids(), self::UID_CHUNK_MAX_BYTES)
			)
		);
	}
}
