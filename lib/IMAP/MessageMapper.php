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

namespace OCA\Mail\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use Html2Text\Html2Text;
use OCA\Mail\Attachment;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use function array_filter;
use function array_map;
use function count;
use function fclose;
use function in_array;
use function iterator_to_array;
use function max;
use function min;
use function sprintf;

class MessageMapper {
	/** @var LoggerInterface */
	private $logger;

	private SMimeService $smimeService;
	private ImapMessageFetcherFactory $imapMessageFactory;

	public function __construct(LoggerInterface           $logger,
								SmimeService              $smimeService,
								ImapMessageFetcherFactory $imapMessageFactory) {
		$this->logger = $logger;
		$this->smimeService = $smimeService;
		$this->imapMessageFactory = $imapMessageFactory;
	}

	/**
	 * @return IMAPMessage
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 */
	public function find(Horde_Imap_Client_Base $client,
						 string $mailbox,
						 int $id,
						 string $userId,
						 bool $loadBody = false): IMAPMessage {
		$result = $this->findByIds($client, $mailbox, new Horde_Imap_Client_Ids([$id]), $userId, $loadBody);

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
							PerformanceLoggerTask $perf,
							string $userId): array {
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
			return $this->findAll($client, $mailbox, $maxResults, $upper, $logger, $perf, $userId);
		}
		$uidCandidates = array_filter(
			array_map(
				static function (Horde_Imap_Client_Data_Fetch $data) {
					return $data->getUid();
				},
				iterator_to_array($fetchResult)
			),

			static function (int $uid) use ($highestKnownUid) {
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
			new Horde_Imap_Client_Ids($fetchRange),
			$userId,
		);
		$perf->step('find IMAP messages by UID');
		return [
			'messages' => $messages,
			'all' => $highestUidToFetch === $max,
			'total' => $total,
		];
	}

	/**
	 * @param Horde_Imap_Client_Base $client
	 * @param string $mailbox
	 * @param Horde_Imap_Client_Ids $ids
	 * @param string $userId
	 * @param bool $loadBody
	 * @return IMAPMessage[]
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws Horde_Mime_Exception
	 * @throws ServiceException
	 */
	public function findByIds(Horde_Imap_Client_Base $client,
							  string $mailbox,
							  Horde_Imap_Client_Ids $ids,
							  string $userId,
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

		/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
		$fetchResults = iterator_to_array($client->fetch($mailbox, $query, [
			'ids' => $ids,
		]), false);

		$fetchResults = array_values(array_filter($fetchResults, static function (Horde_Imap_Client_Data_Fetch $fetchResult) {
			return $fetchResult->exists(Horde_Imap_Client::FETCH_ENVELOPE);
		}));

		if (empty($fetchResults)) {
			$this->logger->debug("findByIds in $mailbox got " . count($ids) . " UIDs but found none");
		} else {
			$minFetched = $fetchResults[0]->getUid();
			$maxFetched = $fetchResults[count($fetchResults) - 1]->getUid();
			$range = $ids->range_string;
			$this->logger->debug("findByIds in $mailbox got " . count($ids) . " UIDs ($range) and found " . count($fetchResults) . ". minFetched=$minFetched maxFetched=$maxFetched");
		}

		return array_map(function (Horde_Imap_Client_Data_Fetch $fetchResult) use ($client, $mailbox, $loadBody, $userId) {
			return $this->imapMessageFactory
				->build(
					$fetchResult->getUid(),
					$mailbox,
					$client,
					$userId,
				)
				->withBody($loadBody)
				->fetchMessage($fetchResult);
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
	 * @param string $userId
	 * @param bool $decrypt
	 * @return string|null
	 *
	 * @throws ServiceException
	 */
	public function getFullText(Horde_Imap_Client_Socket $client,
								string $mailbox,
								int $uid,
								string $userId,
								bool $decrypt = true): ?string {
		$query = new Horde_Imap_Client_Fetch_Query();

		if ($decrypt) {
			$this->smimeService->addDecryptQueries($query);
		} else {
			$query->fullText([ 'peek' => true ]);
		}

		try {
			$result = $client->fetch($mailbox, $query, [
				'ids' => new Horde_Imap_Client_Ids($uid),
			]);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not fetch message source: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}

		if (($message = $result->first()) === null) {
			return null;
		}

		if ($decrypt) {
			return $this->smimeService->decryptDataFetch($message, $userId);
		}

		return $message->getFullMsg();
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param int $uid
	 * @param string $userId
	 * @return string|null
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws Horde_Mime_Exception
	 * @throws ServiceException
	 */
	public function getHtmlBody(Horde_Imap_Client_Socket $client,
								string                   $mailbox,
								int                      $uid,
								string                   $userId): ?string {
		$messageQuery = new Horde_Imap_Client_Fetch_Query();
		$messageQuery->envelope();
		$messageQuery->structure();
		$this->smimeService->addEncryptionCheckQueries($messageQuery, true);

		$result = $client->fetch($mailbox, $messageQuery, [
			'ids' => new Horde_Imap_Client_Ids([$uid]),
		]);

		if (($message = $result->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$structure = $message->getStructure();

		// Handle S/MIME encrypted message
		if ($this->smimeService->isEncrypted($message)) {
			// Encrypted messages have to be fully fetched in order to analyze the structure because
			// it is hidden (obviously).
			$fullText = $this->getFullText($client, $mailbox, $uid, $userId);

			// Force mime parsing as decrypted S/MIME payload doesn't have to contain a MIME header
			$mimePart = Horde_Mime_Part::parseMessage($fullText, ['forcemime' => true ]);
			$htmlPartId = $mimePart->findBody('html');
			if (!isset($mimePart[$htmlPartId])) {
				return null;
			}

			return $mimePart[$htmlPartId];
		}

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

	/**
	 * @deprecated Use getAttachments() instead
	 *
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param int $uid
	 * @param string $userId
	 * @param array|null $attachmentIds
	 * @return array
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws Horde_Mime_Exception
	 * @throws ServiceException
	 */
	public function getRawAttachments(Horde_Imap_Client_Socket $client,
									  string $mailbox,
									  int $uid,
									  string $userId,
									  ?array $attachmentIds = []): array {
		$attachments = $this->getAttachments($client, $mailbox, $uid, $userId, $attachmentIds);
		return array_map(static function (Attachment $attachment) {
			return $attachment->getContent();
		}, $attachments);
	}

	/**
	 * Get Attachments with size, content and name properties
	 *
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param integer $uid
	 * @param string $userId
	 * @param array|null $attachmentIds
	 * @return Attachment[]
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws ServiceException
	 * @throws Horde_Mime_Exception
	 */
	public function getAttachments(Horde_Imap_Client_Socket $client,
								   string $mailbox,
								   int $uid,
								   string $userId,
								   ?array $attachmentIds = []): array {
		$uids = new Horde_Imap_Client_Ids([$uid]);

		$messageQuery = new Horde_Imap_Client_Fetch_Query();
		$messageQuery->structure();
		$this->smimeService->addEncryptionCheckQueries($messageQuery);

		$result = $client->fetch($mailbox, $messageQuery, ['ids' => $uids ]);

		if (($structureResult = $result->first()) === null) {
			throw new DoesNotExistException('Message does not exist');
		}

		$structure = $structureResult->getStructure();
		$messageData = null;

		$isEncrypted = $this->smimeService->isEncrypted($structureResult);
		if ($isEncrypted) {
			$fullTextQuery = new Horde_Imap_Client_Fetch_Query();
			$this->smimeService->addDecryptQueries($fullTextQuery);

			$fullTextParts = $client->fetch($mailbox, $fullTextQuery, ['ids' => $uids ]);
			if (($fullTextResult = $fullTextParts->first()) === null) {
				throw new DoesNotExistException('Message does not exist');
			}
			$decryptedText = $this->smimeService->decryptDataFetch($fullTextResult, $userId);

			// Replace opaque structure with decrypted structure
			$structure = Horde_Mime_Part::parseMessage($decryptedText, [ 'forcemime' => true ]);
		} else {
			$partsQuery = $this->buildAttachmentsPartsQuery($structure, $attachmentIds);
			$parts = $client->fetch($mailbox, $partsQuery, ['ids' => $uids ]);
			if (($messageData = $parts->first()) === null) {
				throw new DoesNotExistException('Message does not exist');
			}
		}

		/** @var Attachment[] $attachments */
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

			// Encrypted parts were already decoded and their content can be used directly
			if (!$isEncrypted) {
				$stream = $messageData->getBodyPart($key, true);
				$mimeHeaders = $messageData->getMimeHeader($key, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
				if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
					$part->setTransferEncoding($enc);
				}
				$part->setContents($stream, [
					'usestream' => true,
				]);
				fclose($stream);
			}

			$attachments[] = Attachment::fromMimePart($part);
		}
		return $attachments;
	}

	/**
	 * @param Horde_Imap_Client_Base $client
	 * @param string $mailbox
	 * @param int $messageUid
	 * @param string $attachmentId
	 * @param string $userId
	 * @return Attachment
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws ServiceException
	 * @throws Horde_Mime_Exception
	 */
	public function getAttachment(Horde_Imap_Client_Base $client,
								  string $mailbox,
								  int $messageUid,
								  string $attachmentId,
								  string $userId): Attachment {
		// TODO: compare logic and merge with getAttachments()

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->bodyPart($attachmentId);
		$query->mimeHeader($attachmentId);
		$this->smimeService->addEncryptionCheckQueries($query);

		$uids = new Horde_Imap_Client_Ids($messageUid);
		$headers = $client->fetch($mailbox, $query, ['ids' => $uids]);
		if (!isset($headers[$messageUid])) {
			throw new DoesNotExistException('Unable to load the attachment.');
		}

		/** @var Horde_Imap_Client_Data_Fetch $fetch */
		$fetch = $headers[$messageUid];

		/** @var Horde_Mime_Headers $mimeHeaders */
		$mimeHeaders = $fetch->getMimeHeader($attachmentId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);

		$body = $fetch->getBodyPart($attachmentId);

		$isEncrypted = $this->smimeService->isEncrypted($fetch);
		if ($isEncrypted) {
			$fullTextQuery = new Horde_Imap_Client_Fetch_Query();
			$this->smimeService->addDecryptQueries($fullTextQuery);

			$result = $client->fetch($mailbox, $fullTextQuery, ['ids' => $uids]);
			if (!isset($result[$messageUid])) {
				throw new DoesNotExistException('Unable to load the attachment.');
			}
			/** @var Horde_Imap_Client_Data_Fetch $fetch */
			$fullTextResult = $result[$messageUid];

			$decryptedText = $this->smimeService->decryptDataFetch($fullTextResult, $userId);
			$decryptedPart = Horde_Mime_Part::parseMessage($decryptedText, [ 'forcemime' => true ]);
			if (!isset($decryptedPart[$attachmentId])) {
				throw new DoesNotExistException('Unable to load the attachment.');
			}

			$attachmentPart = $decryptedPart[$attachmentId];
			$body = $attachmentPart->getContents();
			$mimeHeaders = $attachmentPart->addMimeHeaders();
		}

		$mimePart = new Horde_Mime_Part();

		// Serve all files with a content-disposition of "attachment" to prevent Cross-Site Scripting
		$mimePart->setDisposition('attachment');

		// Extract headers from part
		$contentDisposition = $mimeHeaders->getValue('content-disposition', Horde_Mime_Headers::VALUE_PARAMS);
		if (!is_null($contentDisposition) && isset($contentDisposition['filename'])) {
			$mimePart->setDispositionParameter('filename', $contentDisposition['filename']);
		} else {
			$contentDisposition = $mimeHeaders->getValue('content-type', Horde_Mime_Headers::VALUE_PARAMS);
			if (isset($contentDisposition['name'])) {
				$mimePart->setContentTypeParameter('name', $contentDisposition['name']);
			}
		}

		// Content transfer encoding
		// Decrypted parts are already decoded because they went through the MIME parser
		if (!$isEncrypted && $tmp = $mimeHeaders->getValue('content-transfer-encoding')) {
			$mimePart->setTransferEncoding($tmp);
		}

		/* Content type */
		$contentType = $mimeHeaders->getValue('content-type');
		if (!is_null($contentType) && str_contains($contentType, 'text/calendar')) {
			$mimePart->setType('text/calendar');
			if ($mimePart->getContentTypeParameter('name') === null) {
				$mimePart->setContentTypeParameter('name', 'calendar.ics');
			}
		} else {
			// To prevent potential problems with the SOP we serve all files but calendar entries with the
			// MIME type "application/octet-stream"
			$mimePart->setType('application/octet-stream');
		}

		$mimePart->setContents($body);
		return Attachment::fromMimePart($mimePart);
	}

	/**
	 * Build the parts query for attachments
	 *
	 * @param Horde_Mime_Part $structure
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
		$this->smimeService->addEncryptionCheckQueries($structureQuery);

		$structures = $client->fetch($mailbox, $structureQuery, [
			'ids' => new Horde_Imap_Client_Ids($uids),
		]);

		return array_map(function (Horde_Imap_Client_Data_Fetch $fetchData) use ($mailbox, $client) {
			$hasAttachments = false;
			$text = '';
			$isImipMessage = false;
			$isEncrypted = false;

			if ($this->smimeService->isEncrypted($fetchData)) {
				$isEncrypted = true;
			}

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
				return new MessageStructureData($hasAttachments, $text, $isImipMessage, $isEncrypted);
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
				return new MessageStructureData($hasAttachments, $text, $isImipMessage, $isEncrypted);
			}
			$textBody = $part->getBodyPart($textBodyId);
			if (!empty($textBody)) {
				$mimeHeaders = $part->getMimeHeader($textBodyId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
				if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
					$structure->setTransferEncoding($enc);
					$structure->setContents($textBody);
					$textBody = $structure->getContents();
				}
				return new MessageStructureData(
					$hasAttachments,
					$textBody,
					$isImipMessage,
					$isEncrypted,
				);
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
				return new MessageStructureData(
					$hasAttachments,
					trim($html->getText()),
					$isImipMessage,
					$isEncrypted,
				);
			}
			return new MessageStructureData($hasAttachments, $text, $isImipMessage, $isEncrypted);
		}, iterator_to_array($structures->getIterator()));
	}
}
