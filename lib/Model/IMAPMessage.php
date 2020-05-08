<?php

declare(strict_types=1);

/**
 * @author Alexander Weidinger <alexwegoo@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Mueller <thomas.mueller@tmit.eu>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\Model;

use Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Envelope;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_DateTime;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use Horde_Mime_Part;
use JsonSerializable;
use OC;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Service\Html;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use function base64_encode;
use function in_array;
use function mb_convert_encoding;

class IMAPMessage implements IMessage, JsonSerializable {
	use ConvertAddresses;

	/**
	 * @var string[]
	 */
	private $attachmentsToIgnore = ['signature.asc', 'smime.p7s'];

	/** @var Html|null */
	private $htmlService;

	/**
	 * @param Horde_Imap_Client_Socket|null $conn
	 * @param Horde_Imap_Client_Mailbox|string $mailBox
	 * @param int $messageId
	 * @param Horde_Imap_Client_Data_Fetch|null $fetch
	 * @param bool $loadHtmlMessage
	 * @param Html|null $htmlService
	 *
	 * @throws DoesNotExistException
	 */
	public function __construct($conn,
								$mailBox,
								int $messageId,
								Horde_Imap_Client_Data_Fetch $fetch = null,
								bool $loadHtmlMessage = false,
								Html $htmlService = null) {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
		$this->messageId = $messageId;
		$this->loadHtmlMessage = $loadHtmlMessage;

		$this->htmlService = $htmlService;
		if (is_null($htmlService)) {
			$urlGenerator = OC::$server->getURLGenerator();
			$request = OC::$server->getRequest();
			$this->htmlService = new Html($urlGenerator, $request);
		}

		if ($fetch === null) {
			$this->loadMessageBodies();
		} else {
			$this->fetch = $fetch;
		}
	}

	// output all the following:
	public $header = null;
	public $htmlMessage = '';
	public $plainMessage = '';
	public $attachments = [];
	private $loadHtmlMessage = false;
	private $hasHtmlMessage = false;

	/**
	 * @var Horde_Imap_Client_Socket
	 */
	private $conn;

	/**
	 * @var Horde_Imap_Client_Mailbox
	 */
	private $mailBox;
	private $messageId;

	/**
	 * @var Horde_Imap_Client_Data_Fetch
	 */
	private $fetch;

	/**
	 * @return int
	 */
	public function getUid(): int {
		return $this->fetch->getUid();
	}

	/**
	 * @return array
	 */
	public function getFlags(): array {
		$flags = $this->fetch->getFlags();
		return [
			'seen' => in_array(Horde_Imap_Client::FLAG_SEEN, $flags),
			'flagged' => in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags),
			'answered' => in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags),
			'deleted' => in_array(Horde_Imap_Client::FLAG_DELETED, $flags),
			'draft' => in_array(Horde_Imap_Client::FLAG_DRAFT, $flags),
			'forwarded' => in_array(Horde_Imap_Client::FLAG_FORWARDED, $flags),
			'hasAttachments' => $this->hasAttachments($this->fetch->getStructure())
		];
	}

	/**
	 * @param array $flags
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setFlags(array $flags) {
		// TODO: implement
		throw new Exception('Not implemented');
	}

	/**
	 * @return Horde_Imap_Client_Data_Envelope
	 */
	public function getEnvelope() {
		return $this->fetch->getEnvelope();
	}

	/**
	 * @return AddressList
	 */
	public function getFrom(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->from);
	}

	/**
	 * @param AddressList $from
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setFrom(AddressList $from) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->to);
	}

	/**
	 * @param AddressList $to
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setTo(AddressList $to) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getCC(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->cc);
	}

	/**
	 * @param AddressList $cc
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setCC(AddressList $cc) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getBCC(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->bcc);
	}

	/**
	 * @param AddressList $bcc
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setBcc(AddressList $bcc) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * Get the ID if available
	 *
	 * @return string
	 */
	public function getMessageId(): string {
		return $this->getEnvelope()->message_id;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string {
		return $this->getEnvelope()->subject;
	}

	/**
	 * @param string $subject
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setSubject(string $subject) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return Horde_Imap_Client_DateTime
	 */
	public function getSentDate(): Horde_Imap_Client_DateTime {
		return $this->fetch->getImapDate();
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->fetch->getSize();
	}

	/**
	 * @param Horde_Mime_Part $part
	 *
	 * @return bool
	 */
	private function hasAttachments($part) {
		foreach ($part->getParts() as $p) {
			/** @var Horde_Mime_Part $p */
			$filename = $p->getName();

			if ($p->getContentId() !== null) {
				continue;
			}
			// TODO: show embedded messages and don't treat them as attachments
			if ($p->getType() === 'message/rfc822' || isset($filename)) {
				// do not show technical attachments
				if (in_array($filename, $this->attachmentsToIgnore)) {
					continue;
				}

				return true;
			}
			if ($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function loadMessageBodies(): void {
		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->structure();
		$fetch_query->flags();
		$fetch_query->size();
		$fetch_query->imapDate();

		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$ids = new Horde_Imap_Client_Ids($this->messageId);
		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		/** @var $fetch \Horde_Imap_Client_Data_Fetch */
		$fetch = $headers[$this->messageId];
		if (is_null($fetch)) {
			throw new DoesNotExistException("This email ($this->messageId) can't be found. Probably it was deleted from the server recently. Please reload.");
		}

		// set $this->fetch to get to, from ...
		$this->fetch = $fetch;

		// analyse the body part
		$structure = $fetch->getStructure();

		// debugging below
		$structure_type = $structure->getPrimaryType();
		if ($structure_type === 'multipart') {
			$i = 1;
			foreach ($structure->getParts() as $p) {
				$this->getPart($p, $i++);
			}
		} else {
			if (!is_null($structure->findBody())) {
				// get the body from the server
				$partId = (int)$structure->findBody();
				$this->getPart($structure->getPart($partId), $partId);
			}
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function getPart(Horde_Mime_Part $p, $partNo): void {
		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		$filename = $p->getName();
		// TODO: show embedded messages and don't treat them as attachments
		if ($p->getType() === 'message/rfc822' || isset($filename)) {
			if (in_array($filename, $this->attachmentsToIgnore)) {
				return;
			}
			$this->attachments[] = [
				'id' => $p->getMimeId(),
				'messageId' => $this->messageId,
				'fileName' => $filename,
				'mime' => $p->getType(),
				'size' => $p->getBytes(),
				'cid' => $p->getContentId()
			];
			return;
		}

		if ($p->getPrimaryType() === 'multipart') {
			$this->handleMultiPartMessage($p, $partNo);
			return;
		}

		if ($p->getType() === 'text/plain') {
			$this->handleTextMessage($p, $partNo);
			return;
		}

		// TEXT
		if ($p->getType() === 'text/calendar') {
			// TODO: skip inline ics for now
			return;
		}

		if ($p->getType() === 'text/html') {
			$this->handleHtmlMessage($p, $partNo);
			return;
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		if ($p[0] === 'message') {
			$data = $this->loadBodyData($p, $partNo);
			$this->plainMessage .= trim($data) . "\n\n";
		}
	}

	/**
	 * @return array
	 */
	public function getFullMessage(int $accountId, string $mailbox, int $id): array {
		$mailBody = $this->plainMessage;

		$data = $this->jsonSerialize();
		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
			$data['body'] = $this->getHtmlBody($accountId, $mailbox, $id);
		} else {
			$mailBody = $this->htmlService->convertLinks($mailBody);
			list($mailBody, $signature) = $this->htmlService->parseMailBody($mailBody);
			$data['body'] = $mailBody;
			$data['signature'] = $signature;
		}

		$data['attachments'] = $this->attachments;

		return $data;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getUid(),
			'messageId' => $this->getMessageId(),
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'cc' => $this->getCC()->jsonSerialize(),
			'bcc' => $this->getBCC()->jsonSerialize(),
			'subject' => $this->getSubject(),
			'dateInt' => $this->getSentDate()->getTimestamp(),
			'flags' => $this->getFlags(),
			'hasHtmlBody' => $this->hasHtmlMessage,
		];
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $messageId
	 *
	 * @return string
	 */
	public function getHtmlBody(int $accountId, string $folderId, int $messageId): string {
		return $this->htmlService->sanitizeHtmlMailBody($this->htmlMessage, [
			'accountId' => $accountId,
			'folderId' => base64_encode($folderId),
			'messageId' => $messageId,
		], function ($cid) {
			$match = array_filter($this->attachments,
				function ($a) use ($cid) {
					return $a['cid'] === $cid;
				});
			$match = array_shift($match);
			if ($match === null) {
				return null;
			}
			return $match['id'];
		});
	}

	/**
	 * @return string
	 */
	public function getPlainBody(): string {
		return $this->plainMessage;
	}

	/**
	 * @param Horde_Mime_Part $part
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleMultiPartMessage(Horde_Mime_Part $part, $partNo): void {
		$i = 1;
		foreach ($part->getParts() as $p) {
			$this->getPart($p, "$partNo.$i");
			$i++;
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleTextMessage(Horde_Mime_Part $p, $partNo): void {
		$data = $this->loadBodyData($p, $partNo);
		$this->plainMessage .= trim($data) . "\n\n";
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleHtmlMessage(Horde_Mime_Part $p, $partNo): void {
		$this->hasHtmlMessage = true;
		if ($this->loadHtmlMessage) {
			$data = $this->loadBodyData($p, $partNo);
			$this->htmlMessage .= $data . "<br><br>";
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @return string
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	private function loadBodyData(Horde_Mime_Part $p, $partNo): string {
		// DECODE DATA
		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$ids = new Horde_Imap_Client_Ids($this->messageId);

		$fetch_query->bodyPart($partNo, [
			'peek' => true
		]);
		$fetch_query->bodyPartSize($partNo);
		$fetch_query->mimeHeader($partNo, [
			'peek' => true
		]);

		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		/* @var $fetch Horde_Imap_Client_Data_Fetch */
		$fetch = $headers[$this->messageId];
		if (is_null($fetch)) {
			throw new DoesNotExistException("Mail body for this mail($this->messageId) could not be loaded");
		}

		$mimeHeaders = $fetch->getMimeHeader($partNo, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
			$p->setTransferEncoding($enc);
		}

		$data = $fetch->getBodyPart($partNo);

		$p->setContents($data);
		$data = $p->getContents();

		$data = mb_convert_encoding($data, 'UTF-8', $p->getCharset());
		return $data;
	}

	public function getContent(): string {
		return $this->getPlainBody();
	}

	/**
	 * @return void
	 */
	public function setContent(string $content) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return array
	 */
	public function getCloudAttachments(): array {
		throw new Exception('not implemented');
	}

	/**
	 * @return int[]
	 */
	public function getLocalAttachments(): array {
		throw new Exception('not implemented');
	}

	/**
	 * @param File $file
	 *
	 * @return void
	 */
	public function addAttachmentFromFiles(File $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 *
	 * @return void
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return IMessage|null
	 */
	public function getInReplyTo() {
		throw new Exception('not implemented');
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function setInReplyTo(string $message) {
		throw new Exception('not implemented');
	}

	public function toDbMessage(int $mailboxId): \OCA\Mail\Db\Message {
		$msg = new \OCA\Mail\Db\Message();

		$msg->setUid($this->getUid());
		$msg->setMessageId($this->getMessageId());
		$msg->setMailboxId($mailboxId);
		$msg->setFrom($this->getFrom());
		$msg->setTo($this->getTo());
		$msg->setCc($this->getCc());
		$msg->setBcc($this->getBcc());
		$msg->setSubject(mb_substr($this->getSubject(), 0, 255));
		$msg->setSentAt($this->getSentDate()->getTimestamp());

		$flags = $this->fetch->getFlags();
		$msg->setFlagAnswered(in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags, true));
		$msg->setFlagDeleted(in_array(Horde_Imap_Client::FLAG_DELETED, $flags, true));
		$msg->setFlagDraft(in_array(Horde_Imap_Client::FLAG_DRAFT, $flags, true));
		$msg->setFlagFlagged(in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags, true));
		$msg->setFlagSeen(in_array(Horde_Imap_Client::FLAG_SEEN, $flags, true));
		$msg->setFlagForwarded(in_array(Horde_Imap_Client::FLAG_FORWARDED, $flags, true));
		$msg->setFlagJunk(
			in_array(Horde_Imap_Client::FLAG_JUNK, $flags, true) ||
			in_array('junk', $flags, true)
		);
		$msg->setFlagNotjunk(in_array(Horde_Imap_Client::FLAG_NOTJUNK, $flags, true));
		$msg->setFlagImportant(false);
		$msg->setFlagAttachments(false);

		return $msg;
	}
}
