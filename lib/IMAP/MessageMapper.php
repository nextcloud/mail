<?php

declare(strict_types=1);

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

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use Html2Text\Html2Text;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use function array_filter;
use function array_map;
use function count;
use function fclose;
use function in_array;
use function is_array;
use function iterator_to_array;
use function max;
use function min;
use function OCA\Mail\array_flat_map;
use function OCA\Mail\chunk_uid_sequence;
use function reset;
use function sprintf;

class MessageMapper {
	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * @return IMAPMessage
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 */
	public function find(Horde_Imap_Client_Base $client,
						 string $mailbox,
						 int $id,
						 bool $loadBody = false): IMAPMessage {
		$result = $this->findByIds($client, $mailbox, new Horde_Imap_Client_Ids([$id]), $loadBody);

		if (count($result) === 0) {
			throw new DoesNotExistException("Message does not exist");
		}

		return $result[0];
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 *
	 * @param int $maxResults
	 * @param int $highestKnownUid
	 * @param PerformanceLoggerTask $perf
	 *
	 * @return array
	 * @throws Horde_Imap_Client_Exception
	 */
	public function findAll(Horde_Imap_Client_Socket $client,
							string $mailbox,
							int $maxResults,
							int $highestKnownUid,
							LoggerInterface $logger,
							PerformanceLoggerTask $perf): array {
		/**
		 * To prevent memory exhaustion, we don't want to just ask for a list of
		 * all UIDs and limit them client-side. Instead, we can (hopefully
		 * efficiently) query the min and max UID as well as the number of
		 * messages. Based on that we assume that UIDs are somewhat distributed
		 * equally and build a page to fetch.
		 *
		 * This logic might return fewer or more results than $maxResults
		 */

		$metaResults = $client->search(
			$mailbox,
			null,
			[
				'results' => [
					Horde_Imap_Client::SEARCH_RESULTS_MIN,
					Horde_Imap_Client::SEARCH_RESULTS_MAX,
					Horde_Imap_Client::SEARCH_RESULTS_COUNT,
				]
			]
		);
		$perf->step('mailbox meta search');
		/** @var int $min */
		$min = (int) $metaResults['min'];
		/** @var int $max */
		$max = (int) $metaResults['max'];
		/** @var int $total */
		$total = (int) $metaResults['count'];

		if ($total === 0) {
			// Nothing to fetch for this mailbox
			return [
				'messages' => [],
				'all' => true,
				'total' => $total,
			];
		}

		// The inclusive range of UIDs
		$totalRange = $max - $min + 1;
		// Here we assume somewhat equally distributed UIDs
		// +1 is added to fetch all messages with the rare case of strictly
		// continuous UIDs and fractions
		$estimatedPageSize = (int)(($totalRange / $total) * $maxResults) + 1;
		// Determine min UID to fetch, but don't exceed the known maximum
		$lower = max(
			$min,
			$highestKnownUid + 1
		);
		// Determine max UID to fetch, but don't exceed the known maximum
		$upper = min(
			$max,
			$lower + $estimatedPageSize
		);
		if ($lower > $upper) {
			$logger->debug("Range for findAll did not find any (not already known) messages and all messages of mailbox $mailbox have been fetched.");
			return [
				'messages' => [],
				'all' => true,
				'total' => 0,
			];
		}

		$logger->debug("Built range for findAll: min=$min max=$max total=$total totalRange=$totalRange estimatedPageSize=$estimatedPageSize lower=$lower upper=$upper highestKnownUid=$highestKnownUid");

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$fetchResult = $client->fetch(
			$mailbox,
			$query,
			[
				'ids' => new Horde_Imap_Client_Ids($lower . ':' . $upper)
			]
		);
		$perf->step('fetch UIDs');
		if (count($fetchResult) === 0) {
			/*
			 * There were no messages in this range.
			 * This means we should try again until there is a
			 * page that actually returns at least one message
			 *
			 * We take $upper as the lowest known UID as we just found out that
			 * there is nothing to fetch in $highestKnownUid:$upper
			 */
			$logger->debug("Range for findAll did not find any messages. Trying again with a succeeding range");
			return $this->findAll($client, $mailbox, $maxResults, $upper, $logger, $perf);
		}
		$uidCandidates = array_filter(
			array_map(
				function (Horde_Imap_Client_Data_Fetch $data) {
					return $data->getUid();
				},
				iterator_to_array($fetchResult)
			),

			function (int $uid) use ($highestKnownUid) {
				// Don't load the ones we already know
				return $uid > $highestKnownUid;
			}
		);
		$uidsToFetch = array_slice(
			$uidCandidates,
			0,
			$maxResults
		);
		$perf->step('calculate UIDs to fetch');
		$highestUidToFetch = $uidsToFetch[count($uidsToFetch) - 1];
		$logger->debug(sprintf("Range for findAll min=$min max=$max found %d messages, %d left after filtering. Highest UID to fetch is %d", count($uidCandidates), count($uidsToFetch), $highestUidToFetch));
		$fetchRange = min($uidsToFetch) . ':' . max($uidsToFetch);
		if ($highestUidToFetch === $max) {
			$logger->debug("All messages of mailbox $mailbox have been fetched");
		} else {
			$logger->debug("Mailbox $mailbox has more messages to fetch: $fetchRange");
		}
		$messages = $this->findByIds(
			$client,
			$mailbox,
			new Horde_Imap_Client_Ids($fetchRange)
		);
		$perf->step('find IMAP messages by UID');
		return [
			'messages' => $messages,
			'all' => $highestUidToFetch === $max,
			'total' => $total,
		];
	}

	/**
	 * @param int[]|Horde_Imap_Client_Ids $ids
	 * @return IMAPMessage[]
	 * @throws Horde_Imap_Client_Exception
	 */
	public function findByIds(Horde_Imap_Client_Base $client,
							  string $mailbox,
							  $ids,
							  bool $loadBody = false): array {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->envelope();
		$query->flags();
		$query->uid();
		$query->imapDate();
		$query->headerText(
			[
				'cache' => true,
				'peek' => true,
			]
		);

		if (is_array($ids)) {
			// Chunk to prevent overly long IMAP commands
			/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
			$fetchResults = array_flat_map(function ($ids) use ($query, $mailbox, $client) {
				return iterator_to_array($client->fetch($mailbox, $query, [
					'ids' => $ids,
				]), false);
			}, chunk_uid_sequence($ids, 10000));
		} else {
			/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
			$fetchResults = iterator_to_array($client->fetch($mailbox, $query, [
				'ids' => $ids,
			]), false);
		}

		$fetchResults = array_values(array_filter($fetchResults, static function (Horde_Imap_Client_Data_Fetch $fetchResult) {
			return $fetchResult->exists(Horde_Imap_Client::FETCH_ENVELOPE);
		}));

		if (empty($fetchResults)) {
			$this->logger->debug("findByIds in $mailbox got " . count($ids) . " UIDs but found none");
		} else {
			$minFetched = $fetchResults[0]->getUid();
			$maxFetched = $fetchResults[count($fetchResults) - 1]->getUid();
			if ($ids instanceof Horde_Imap_Client_Ids) {
				$range = $ids->range_string;
			} else {
				$range = 'literals';
			}
			$this->logger->debug("findByIds in $mailbox got " . count($ids) . " UIDs ($range) and found " . count($fetchResults) . ". minFetched=$minFetched maxFetched=$maxFetched");
		}

		return array_map(function (Horde_Imap_Client_Data_Fetch $fetchResult) use ($client, $mailbox, $loadBody) {
			if ($loadBody) {
				return new IMAPMessage(
					$client,
					$mailbox,
					$fetchResult->getUid(),
					null,
					$loadBody
				);
			} else {
				return new IMAPMessage(
					$client,
					$mailbox,
					$fetchResult->getUid(),
					$fetchResult
				);
			}
		}, $fetchResults);
	}

	/**
	 * @param Horde_Imap_Client_Base $client
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param string $destFolderId
	 */
	public function move(Horde_Imap_Client_Base $client,
						 string $sourceFolderId,
						 int $messageId,
						 string $destFolderId): void {
		try {
			$client->copy($sourceFolderId, $destFolderId,
				[
					'ids' => new Horde_Imap_Client_Ids($messageId),
					'move' => true,
				]);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->debug($e->getMessage(),
				[
					'exception' => $e,
				]
			);

			throw new ServiceException(
				"Could not move message $$messageId from $sourceFolderId to $destFolderId",
				0,
				$e
			);
		}
	}

	public function markAllRead(Horde_Imap_Client_Base $client,
								string $mailbox): void {
		$client->store($mailbox, [
			'add' => [
				Horde_Imap_Client::FLAG_SEEN,
			],
		]);
	}

	/**
	 * @throws ServiceException
	 */
	public function expunge(Horde_Imap_Client_Base $client,
							string $mailbox,
							int $id): void {
		try {
			$client->expunge(
				$mailbox,
				[
					'ids' => new Horde_Imap_Client_Ids([$id]),
					'delete' => true,
				]);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->debug($e->getMessage(),
				[
					'exception' => $e,
				]
			);

			throw new ServiceException("Could not expunge message $id", 0, $e);
		}

		$this->logger->info("Message expunged: $id from mailbox $mailbox");
	}

	/**
	 * @throws Horde_Imap_Client_Exception
	 */
	public function save(Horde_Imap_Client_Socket $client,
						 Mailbox $mailbox,
						 Horde_Mime_Mail $mail,
						 array $flags = []): int {
		$flags = array_merge([
			Horde_Imap_Client::FLAG_SEEN,
		], $flags);

		$uids = $client->append(
			$mailbox->getName(),
			[
				[
					'data' => $mail->getRaw(),
					'flags' => $flags,
				]
			]
		);

		return (int)$uids->current();
	}

	/**
	 * @throws Horde_Imap_Client_Exception
	 */
	public function addFlag(Horde_Imap_Client_Socket $client,
							Mailbox $mailbox,
							array $uids,
							string $flag): void {
		$client->store(
			$mailbox->getName(),
			[
				'ids' => new Horde_Imap_Client_Ids($uids),
				'add' => [$flag],
			]
		);
	}

	/**
	 * @throws Horde_Imap_Client_Exception
	 */
	public function removeFlag(Horde_Imap_Client_Socket $client,
							   Mailbox $mailbox,
							   array $uids,
							   string $flag): void {
		$client->store(
			$mailbox->getName(),
			[
				'ids' => new Horde_Imap_Client_Ids($uids),
				'remove' => [$flag],
			]
		);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param Mailbox $mailbox
	 * @param string $flag
	 * @return int[]
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getFlagged(Horde_Imap_Client_Socket $client,
							   Mailbox $mailbox,
							   string $flag): array {
		$query = new Horde_Imap_Client_Search_Query();
		$query->flag($flag, true);
		$messages = $client->search($mailbox->getName(), $query);
		return $messages['match']->ids ?? [];
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string|null
	 * @throws ServiceException
	 */
	public function getFullText(Horde_Imap_Client_Socket $client,
								string $mailbox,
								int $uid): ?string {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$query->fullText([
			'peek' => true,
		]);

		try {
			$result = iterator_to_array($client->fetch($mailbox, $query, [
				'ids' => new Horde_Imap_Client_Ids($uid),
			]), false);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not fetch message source: " . $e->getMessage(),
				(int) $e->getCode(),
				$e
			);
		}

		$msg = array_map(function (Horde_Imap_Client_Data_Fetch $result) {
			return $result->getFullMsg();
		}, $result);

		if (empty($msg)) {
			return null;
		}

		return reset($msg);
	}

	public function getHtmlBody(Horde_Imap_Client_Socket $client,
								string $mailbox,
								int $uid): ?string {
		$messageQuery = new Horde_Imap_Client_Fetch_Query();
		$messageQuery->envelope();
		$messageQuery->structure();

		$result = $client->fetch($mailbox, $messageQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);

		if (($message = $result->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$structure = $message->getStructure();
		$htmlPartId = $structure->findBody('html');
		if ($htmlPartId === null) {
			// No HTML part
			return null;
		}
		$partsQuery = $this->buildAttachmentsPartsQuery($structure, [$htmlPartId]);

		$parts = $client->fetch($mailbox, $partsQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);

		foreach ($parts as $part) {
			/** @var Horde_Imap_Client_Data_Fetch $part */
			$body = $part->getBodyPart($htmlPartId);
			if ($body !== null) {
				$mimeHeaders = $part->getMimeHeader($htmlPartId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
				if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
					$structure->setTransferEncoding($enc);
				}
				$structure->setContents($body);
				return $structure->getContents();
			}
		}

		return null;
	}

	public function getRawAttachments(Horde_Imap_Client_Socket $client,
									string $mailbox,
									int $uid,
									?array $attachmentIds = []): array {
		$messageQuery = new Horde_Imap_Client_Fetch_Query();
		$messageQuery->structure();

		$result = $client->fetch($mailbox, $messageQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);

		if (($structureResult = $result->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$structure = $structureResult->getStructure();
		$partsQuery = $this->buildAttachmentsPartsQuery($structure, $attachmentIds);

		$parts = $client->fetch($mailbox, $partsQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);
		if (($messageData = $parts->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$attachments = [];
		foreach ($structure->partIterator() as $key => $part) {
			/** @var Horde_Mime_Part $part */

			if (!$part->isAttachment()) {
				continue;
			}

			if (!empty($attachmentIds) && !in_array($part->getMimeId(), $attachmentIds, true)) {
				// We are looking for specific parts only and this is not one of them
				continue;
			}

			$stream = $messageData->getBodyPart($key, true);
			$mimeHeaders = $messageData->getMimeHeader($key, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
			if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
				$part->setTransferEncoding($enc);
			}
			$part->setContents($stream, [
				'usestream' => true,
			]);
			$decoded = $part->getContents();
			fclose($stream);

			$attachments[] = $decoded;
		}
		return $attachments;
	}

	/**
	 * Get Attachments with size, content and name properties
	 *
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param integer $uid
	 * @param array|null $attachmentIds
	 * @return array[]
	 */
	public function getAttachments(Horde_Imap_Client_Socket $client,
									string $mailbox,
									int $uid,
									?array $attachmentIds = []): array {
		$messageQuery = new Horde_Imap_Client_Fetch_Query();
		$messageQuery->structure();

		$result = $client->fetch($mailbox, $messageQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);

		if (($structureResult = $result->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$structure = $structureResult->getStructure();
		$partsQuery = $this->buildAttachmentsPartsQuery($structure, $attachmentIds);

		$parts = $client->fetch($mailbox, $partsQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);
		if (($messageData = $parts->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$attachments = [];
		foreach ($structure->partIterator() as $key => $part) {
			/** @var Horde_Mime_Part $part */

			if (!$part->isAttachment()) {
				continue;
			}

			if (!empty($attachmentIds) && !in_array($part->getMimeId(), $attachmentIds, true)) {
				// We are looking for specific parts only and this is not one of them
				continue;
			}

			$stream = $messageData->getBodyPart($key, true);
			$mimeHeaders = $messageData->getMimeHeader($key, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
			if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
				$part->setTransferEncoding($enc);
			}
			$part->setContents($stream, [
				'usestream' => true,
			]);
			$attachments[] = [
				'content' => $part->getContents(),
				'name' => $part->getName(),
				'size' => $part->getBytes()
			];
			fclose($stream);
		}
		return $attachments;
	}

	/**
	 * Build the parts query for attachments
	 *
	 * @param $structure
	 * @param array $attachmentIds
	 * @return Horde_Imap_Client_Fetch_Query
	 */
	private function buildAttachmentsPartsQuery(Horde_Mime_Part $structure, array $attachmentIds) : Horde_Imap_Client_Fetch_Query {
		$partsQuery = new Horde_Imap_Client_Fetch_Query();
		$partsQuery->fullText();
		foreach ($structure->partIterator() as $part) {
			/** @var Horde_Mime_Part $part */
			if ($part->getMimeId() === '0') {
				// Ignore message header
				continue;
			}

			if (!empty($attachmentIds) && !in_array($part->getMimeId(), $attachmentIds, true)) {
				// We are looking for specific parts only and this is not one of them
				continue;
			}

			$partsQuery->bodyPart($part->getMimeId(), [
				'peek' => true,
			]);
			$partsQuery->mimeHeader($part->getMimeId(), [
				'peek' => true
			]);
			$partsQuery->bodyPartSize($part->getMimeId());
		}
		return $partsQuery;
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param int[] $uids
	 *
	 * @return MessageStructureData[]
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getBodyStructureData(Horde_Imap_Client_Socket $client,
										 string $mailbox,
										 array $uids): array {
		$structureQuery = new Horde_Imap_Client_Fetch_Query();
		$structureQuery->structure();
		$structureQuery->headerText([
			'cache' => true,
			'peek' => true,
		]);

		$structures = $client->fetch($mailbox, $structureQuery, [
			'ids' => new Horde_Imap_Client_Ids($uids),
		]);

		return array_map(function (Horde_Imap_Client_Data_Fetch $fetchData) use ($mailbox, $client) {
			$hasAttachments = false;
			$text = '';
			$isImipMessage = false;

			$structure = $fetchData->getStructure();
			/** @var Horde_Mime_Part $part */
			foreach ($structure->getParts() as $part) {
				if ($part->isAttachment()) {
					$hasAttachments = true;
				}
				$bodyParts = $part->getParts();
				/** @var Horde_Mime_Part $bodyPart */
				foreach ($bodyParts as $bodyPart) {
					$contentParameters = $bodyPart->getAllContentTypeParameters();
					if ($bodyPart->getType() === 'text/calendar' && isset($contentParameters['method'])) {
						$isImipMessage = true;
					}
				}
			}

			$textBodyId = $structure->findBody() ?? $structure->findBody('text');
			$htmlBodyId = $structure->findBody('html');
			if ($textBodyId === null && $htmlBodyId === null) {
				return new MessageStructureData($hasAttachments, $text, $isImipMessage);
			}
			$partsQuery = new Horde_Imap_Client_Fetch_Query();
			if ($htmlBodyId !== null) {
				$partsQuery->bodyPart($htmlBodyId, [
					'peek' => true,
				]);
				$partsQuery->mimeHeader($htmlBodyId, [
					'peek' => true
				]);
			}
			if ($textBodyId !== null) {
				$partsQuery->bodyPart($textBodyId, [
					'peek' => true,
				]);
				$partsQuery->mimeHeader($textBodyId, [
					'peek' => true
				]);
			}
			$parts = $client->fetch($mailbox, $partsQuery, [
				'ids' => new Horde_Imap_Client_Ids([$fetchData->getUid()]),
			]);
			/** @var Horde_Imap_Client_Data_Fetch $part */
			$part = $parts[$fetchData->getUid()];
			// This is sus - why does this even happen? A delete / move in the middle of this processing?
			if ($part === null) {
				return new MessageStructureData($hasAttachments, $text, $isImipMessage);
			}
			$textBody = $part->getBodyPart($textBodyId);
			if (!empty($textBody)) {
				$mimeHeaders = $part->getMimeHeader($textBodyId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
				if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
					$structure->setTransferEncoding($enc);
					$structure->setContents($textBody);
					$textBody = $structure->getContents();
				}
				return new MessageStructureData($hasAttachments, $textBody, $isImipMessage);
			}

			$htmlBody = ($htmlBodyId !== null) ? $part->getBodyPart($htmlBodyId) : null;
			if (!empty($htmlBody)) {
				$mimeHeaders = $part->getMimeHeader($htmlBodyId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
				if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
					$structure->setTransferEncoding($enc);
					$structure->setContents($htmlBody);
					$htmlBody = $structure->getContents();
				}
				// TODO:  add 'alt_image' => 'hide' once it's added to the Html2Text package
				$html = new Html2Text($htmlBody, array('do_links' => 'none',));
				return new MessageStructureData($hasAttachments, trim($html->getText()), $isImipMessage);
			}
			return new MessageStructureData($hasAttachments, $text, $isImipMessage);
		}, iterator_to_array($structures->getIterator()));
	}
}
