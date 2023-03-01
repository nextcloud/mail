<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Envelope;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Part;
use OCA\Mail\AddressList;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Html;
use OCA\Mail\Service\SmimeService;
use OCP\AppFramework\Db\DoesNotExistException;

class ImapMessageFetcher {
	/** @var string[] */
	private array $attachmentsToIgnore = ['signature.asc', 'smime.p7s'];

	private Html $htmlService;
	private SmimeService $smimeService;
	private string $userId;

	// Conditional fetching/parsing
	private bool $loadBody = false;

	private int $uid;
	private Horde_Imap_Client_Base $client;
	private string $htmlMessage = '';
	private string $plainMessage = '';
	private array $attachments = [];
	private array $inlineAttachments = [];
	private bool $hasAnyAttachment = false;
	private array $scheduling = [];
	private bool $hasHtmlMessage = false;
	private string $mailbox;
	private string $rawReferences = '';
	private string $dispositionNotificationTo = '';

	public function __construct(int $uid,
								string $mailbox,
								Horde_Imap_Client_Base $client,
								string $userId,
								Html $htmlService,
								SmimeService $smimeService) {
		$this->uid = $uid;
		$this->mailbox = $mailbox;
		$this->client = $client;
		$this->userId = $userId;
		$this->htmlService = $htmlService;
		$this->smimeService = $smimeService;
	}


	/**
	 * Configure the fetcher to fetch the body of the message.
	 *
	 * @param bool $value
	 * @return $this
	 */
	public function withBody(bool $value): ImapMessageFetcher {
		$this->loadBody = $value;
		return $this;
	}

	/**
	 * @param Horde_Imap_Client_Data_Fetch|null $fetch
	 * Will be reused if no body is requested.
	 * It should at least contain envelope, flags, imapDate and headerText.
	 * Otherwise, some data might not be parsed correctly.
	 * @return IMAPMessage
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws Horde_Mime_Exception
	 * @throws ServiceException
	 */
	public function fetchMessage(?Horde_Imap_Client_Data_Fetch $fetch = null): IMAPMessage {
		$ids = new Horde_Imap_Client_Ids($this->uid);

		$isSigned = false;
		$signatureIsValid = false;
		$isEncrypted = false;

		if ($this->loadBody) {
			// Ignore given query because lots of data needs to be fetched anyway
			// TODO: reuse given query if beneficial for performance and worth the refactoring effort
			$query = new Horde_Imap_Client_Fetch_Query();
			$query->envelope();
			$query->structure();
			$query->flags();
			$query->imapDate();
			$query->headerText([
				'peek' => true,
			]);
			$this->smimeService->addEncryptionCheckQueries($query);

			$headers = $this->client->fetch($this->mailbox, $query, ['ids' => $ids]);
			/** @var Horde_Imap_Client_Data_Fetch $fetch */
			$fetch = $headers[$this->uid];
			if (is_null($fetch)) {
				throw new DoesNotExistException("This email ($this->uid) can't be found. Probably it was deleted from the server recently. Please reload.");
			}

			// analyse the body part
			$structure = $fetch->getStructure();

			$this->hasAnyAttachment = $this->hasAttachments($structure);

			$isEncrypted = $this->smimeService->isEncrypted($fetch);
			$isOpaqueSigned = $structure->getContentTypeParameter('smime-type') === 'signed-data'
				&& ($structure->getType() === 'application/pkcs7-mime'
					|| $structure->getType() === 'application/x-pkcs7-mime');
			if ($isEncrypted) {
				// Fetch and parse full text if message is encrypted in order to analyze the
				// structure. Conditional fetching doesn't work for encrypted messages.

				$query = new Horde_Imap_Client_Fetch_Query();
				$query->envelope();
				$query->flags();
				$query->imapDate();
				$query->headerText([
					'peek' => true,
				]);
				$this->smimeService->addDecryptQueries($query, true);

				$headers = $this->client->fetch($this->mailbox, $query, ['ids' => $ids]);
				/** @var Horde_Imap_Client_Data_Fetch $fullTextFetch */
				$fullTextFetch = $headers[$this->uid];
				if (is_null($fullTextFetch)) {
					throw new DoesNotExistException("This email ($this->uid) can't be found. Probably it was deleted from the server recently. Please reload.");
				}

				$decryptedText = $this->smimeService->decryptDataFetch($fullTextFetch, $this->userId);
				$structure = Horde_Mime_Part::parseMessage($decryptedText, [
					'forcemime' => true,
				]);
			} elseif ($isOpaqueSigned || $structure->getType() === 'multipart/signed') {
				$query = new Horde_Imap_Client_Fetch_Query();
				$query->fullText([
					'peek' => true,
				]);

				$headers = $this->client->fetch($this->mailbox, $query, ['ids' => $ids]);
				/** @var Horde_Imap_Client_Data_Fetch $fullTextFetch */
				$fullTextFetch = $headers[$this->uid];
				if (is_null($fullTextFetch)) {
					throw new DoesNotExistException("This email ($this->uid) can't be found. Probably it was deleted from the server recently. Please reload.");
				}

				$signedText = $fullTextFetch->getFullMsg();
				$isSigned = true;
				$signatureIsValid = $this->smimeService->verifyMessage($signedText);

				// Extract opaque signed content (smime-type="signed-data")
				if ($isOpaqueSigned) {
					$signedText = $this->smimeService->extractSignedContent($signedText);
				}

				$structure = Horde_Mime_Part::parseMessage($signedText, [
					'forcemime' => true,
				]);
			}

			// debugging below
			$structure_type = $structure->getPrimaryType();
			if ($structure_type === 'multipart') {
				$i = 1;
				foreach ($structure->getParts() as $p) {
					$this->getPart($p, (string)$i++, $isEncrypted || $isSigned);
				}
			} else {
				$bodyPartId = $structure->findBody();
				if (!is_null($bodyPartId)) {
					$this->getPart($structure[$bodyPartId], $bodyPartId, $isEncrypted || $isSigned);
				}
			}
		} elseif (is_null($fetch)) {
			// Reuse given query or construct a new minimal one
			$query = new Horde_Imap_Client_Fetch_Query();
			$query->envelope();
			$query->flags();
			$query->imapDate();
			$query->headerText([
				'peek' => true,
			]);

			$result = $this->client->fetch($this->mailbox, $query, ['ids' => $ids]);
			$fetch = $result[$this->uid];
			if (is_null($fetch)) {
				throw new DoesNotExistException("This email ($this->uid) can't be found. Probably it was deleted from the server recently. Please reload.");
			}
		}

		$this->parseHeaders($fetch);

		$envelope = $fetch->getEnvelope();
		return new IMAPMessage(
			$this->uid,
			$envelope->message_id,
			$fetch->getFlags(),
			AddressList::fromHorde($envelope->from),
			AddressList::fromHorde($envelope->to),
			AddressList::fromHorde($envelope->cc),
			AddressList::fromHorde($envelope->bcc),
			AddressList::fromHorde($envelope->reply_to),
			$this->decodeSubject($envelope),
			$this->plainMessage,
			$this->htmlMessage,
			$this->hasHtmlMessage,
			$this->attachments,
			$this->inlineAttachments,
			$this->hasAnyAttachment,
			$this->scheduling,
			$fetch->getImapDate(),
			$this->rawReferences,
			$this->dispositionNotificationTo,
			$envelope->in_reply_to,
			$isEncrypted,
			$isSigned,
			$signatureIsValid,
			$this->htmlService, // TODO: drop the html service dependency
		);
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param string $partNo
	 * @param bool $isFetched Body is already fetched and contained within the mime part object
	 * @return void
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 */
	private function getPart(Horde_Mime_Part $p, string $partNo, bool $isFetched): void {
		// iMIP messages
		// Handle text/calendar parts first because they might be attachments at the same time.
		// Otherwise, some of the following if-conditions might break the handling and treat iMIP
		// data like regular attachments.
		$allContentTypeParameters = $p->getAllContentTypeParameters();
		if ($p->getType() === 'text/calendar') {
			// Handle event data like a regular attachment
			// Outlook doesn't set a content disposition
			// We work around this by checking for the name only
			if ($p->getName() !== null) {
				$this->attachments[] = [
					'id' => $p->getMimeId(),
					'messageId' => $this->uid,
					'fileName' => $p->getName(),
					'mime' => $p->getType(),
					'size' => $p->getBytes(),
					'cid' => $p->getContentId(),
					'disposition' => $p->getDisposition()
				];
			}

			// return if this is an event attachment only
			// the method parameter determines if this is a iMIP message
			if (!isset($allContentTypeParameters['method'])) {
				return;
			}

			if (in_array(strtoupper($allContentTypeParameters['method']), ['REQUEST', 'REPLY', 'CANCEL'])) {
				$this->scheduling[] = [
					'id' => $p->getMimeId(),
					'messageId' => $this->uid,
					'method' => strtoupper($allContentTypeParameters['method']),
					'contents' => $this->loadBodyData($p, $partNo, $isFetched),
				];
				return;
			}
		}

		// Regular attachments
		if ($p->isAttachment() || $p->getType() === 'message/rfc822') {
			$this->attachments[] = [
				'id' => $p->getMimeId(),
				'messageId' => $this->uid,
				'fileName' => $p->getName(),
				'mime' => $p->getType(),
				'size' => $p->getBytes(),
				'cid' => $p->getContentId(),
				'disposition' => $p->getDisposition()
			];
			return;
		}

		// Inline attachments
		// Horde doesn't consider parts with content-disposition set to inline as
		// attachment so we need to use another way to get them.
		// We use these inline attachments to render a message's html body in $this->getHtmlBody()
		$filename = $p->getName();
		if ($p->getType() === 'message/rfc822' || isset($filename)) {
			if (in_array($filename, $this->attachmentsToIgnore)) {
				return;
			}
			$this->inlineAttachments[] = [
				'id' => $p->getMimeId(),
				'messageId' => $this->uid,
				'fileName' => $filename,
				'mime' => $p->getType(),
				'size' => $p->getBytes(),
				'cid' => $p->getContentId()
			];
			return;
		}

		if ($p->getPrimaryType() === 'multipart') {
			$this->handleMultiPartMessage($p, $partNo, $isFetched);
			return;
		}

		if ($p->getType() === 'text/plain') {
			$this->handleTextMessage($p, $partNo, $isFetched);
			return;
		}

		if ($p->getType() === 'text/html') {
			$this->handleHtmlMessage($p, $partNo, $isFetched);
			return;
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		if ($p[0] === 'message') {
			$data = $this->loadBodyData($p, $partNo, $isFetched);
			$this->plainMessage .= trim($data) . "\n\n";
		}
	}

	/**
	 * @param Horde_Mime_Part $part
	 * @param string $partNo
	 * @param bool $isFetched Body is already fetched and contained within the mime part object
	 * @return void
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 */
	private function handleMultiPartMessage(Horde_Mime_Part $part, string $partNo, bool $isFetched): void {
		$i = 1;
		foreach ($part->getParts() as $p) {
			$this->getPart($p, "$partNo.$i", $isFetched);
			$i++;
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param string $partNo
	 * @param bool $isFetched Body is already fetched and contained within the mime part object
	 * @return void
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 */
	private function handleTextMessage(Horde_Mime_Part $p, string $partNo, bool $isFetched): void {
		$data = $this->loadBodyData($p, $partNo, $isFetched);
		$this->plainMessage .= trim($data) . "\n\n";
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param string $partNo
	 * @param bool $isFetched Body is already fetched and contained within the mime part object
	 * @return void
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 */
	private function handleHtmlMessage(Horde_Mime_Part $p, string $partNo, bool $isFetched): void {
		$this->hasHtmlMessage = true;
		$data = $this->loadBodyData($p, $partNo, $isFetched);
		$this->htmlMessage .= $data . "<br><br>";
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param string $partNo
	 * @param bool $isFetched Body is already fetched and contained within the mime part object
	 * @return string
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 */
	private function loadBodyData(Horde_Mime_Part $p, string $partNo, bool $isFetched): string {
		if (!$isFetched) {
			$fetch_query = new Horde_Imap_Client_Fetch_Query();
			$ids = new Horde_Imap_Client_Ids($this->uid);

			$fetch_query->bodyPart($partNo, [
				'peek' => true
			]);
			$fetch_query->bodyPartSize($partNo);
			$fetch_query->mimeHeader($partNo, [
				'peek' => true
			]);

			$headers = $this->client->fetch($this->mailbox, $fetch_query, ['ids' => $ids]);
			/* @var $fetch Horde_Imap_Client_Data_Fetch */
			$fetch = $headers[$this->uid];
			if (is_null($fetch)) {
				throw new DoesNotExistException("Mail body for this mail($this->uid) could not be loaded");
			}

			$mimeHeaders = $fetch->getMimeHeader($partNo, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
			if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
				$p->setTransferEncoding($enc);
			}

			$data = $fetch->getBodyPart($partNo);

			$p->setContents($data);
		}

		$data = $p->getContents();
		if ($data === null) {
			return '';
		}

		// Only convert encoding if it is explicitly specified in the header because text/calendar
		// data is utf-8 by default.
		$charset = $p->getContentTypeParameter('charset');
		if ($charset !== null && strtoupper($charset) !== 'UTF-8') {
			$data = mb_convert_encoding($data, 'UTF-8', $charset);
		}
		return (string)$data;
	}

	private function hasAttachments(Horde_Mime_Part $part): bool {
		foreach ($part->getParts() as $p) {
			if ($p->isAttachment() || $p->getType() === 'message/rfc822') {
				return true;
			}
			if ($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function decodeSubject(Horde_Imap_Client_Data_Envelope $envelope): string {
		// Try a soft conversion first (some installations, eg: Alpine linux,
		// have issues with the '//IGNORE' option)
		$subject = $envelope->subject;
		$utf8 = iconv('UTF-8', 'UTF-8', $subject);
		if ($utf8 !== false) {
			return $utf8;
		}
		return iconv("UTF-8", "UTF-8//IGNORE", $subject);
	}

	private function parseHeaders(Horde_Imap_Client_Data_Fetch $fetch): void {
		/** @var resource $headersStream */
		$headersStream = $fetch->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headersStream);
		fclose($headersStream);

		$references = $parsedHeaders->getHeader('references');
		if ($references !== null) {
			$this->rawReferences = $references->value_single;
		}

		$dispositionNotificationTo = $parsedHeaders->getHeader('disposition-notification-to');
		if ($dispositionNotificationTo !== null) {
			$this->dispositionNotificationTo = $dispositionNotificationTo->value_single;
		}
	}
}
