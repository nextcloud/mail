<?php

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

use Closure;
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
use OCP\Util;
use function mb_convert_encoding;

class IMAPMessage implements IMessage, JsonSerializable {

	use ConvertAddresses;

	/**
	 * @var string[]
	 */
	private $attachmentsToIgnore = ['signature.asc', 'smime.p7s'];

	/** @var string */
	private $uid;

	/**
	 * @param Horde_Imap_Client_Socket|null $conn
	 * @param Horde_Imap_Client_Mailbox $mailBox
	 * @param integer $messageId
	 * @param \Horde_Imap_Client_Data_Fetch|null $fetch
	 * @param boolean $loadHtmlMessage
	 * @param Html|null $htmlService
	 */
	public function __construct($conn, $mailBox, $messageId, $fetch = null, $loadHtmlMessage = false, $htmlService = null) {
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
	 * @var \Horde_Imap_Client_Data_Fetch
	 */
	private $fetch;

	/**
	 * @return int
	 */
	public function getUid() {
		if (!is_null($this->uid)) {
			return $this->uid;
		}
		return $this->fetch->getUid();
	}

	public function setUid($uid) {
		$this->uid = $uid;
		$this->attachments = array_map(function($attachment) use ($uid) {
			$attachment['messageId'] = $uid;
			return $attachment;
		}, $this->attachments);
	}

	/**
	 * @return array
	 */
	public function getFlags() {
		$flags = $this->fetch->getFlags();
		return [
			'unseen' => !in_array(Horde_Imap_Client::FLAG_SEEN, $flags),
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
	public function getFrom() {
		return AddressList::fromHorde($this->getEnvelope()->from);
	}

	/**
	 * @param AddressList $from
	 * @throws Exception
	 */
	public function setFrom(AddressList $from) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getTo() {
		return AddressList::fromHorde($this->getEnvelope()->to);
	}

	/**
	 * @param AddressList $to
	 */
	public function setTo(AddressList $to) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getCC() {
		return AddressList::fromHorde($this->getEnvelope()->cc);
	}

	/**
	 * @param AddressList $cc
	 */
	public function setCC(AddressList $cc) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return AddressList
	 */
	public function getBCC() {
		return AddressList::fromHorde($this->getEnvelope()->bcc);
	}

	/**
	 * @param AddressList $bcc
	 */
	public function setBcc(AddressList $bcc) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * Get the ID if available
	 *
	 * @return int|null
	 */
	public function getMessageId() {
		$e = $this->getEnvelope();
		return $e->message_id;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		$e = $this->getEnvelope();
		return $e->subject;
	}

	/**
	 * @param string $subject
	 * @throws Exception
	 */
	public function setSubject($subject) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return Horde_Imap_Client_DateTime
	 */
	public function getSentDate() {
		return $this->fetch->getImapDate();
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return $this->fetch->getSize();
	}

	/**
	 * @param Horde_Mime_Part $part
	 * @return bool
	 */
	private function hasAttachments($part) {
		foreach ($part->getParts() as $p) {
			/**
			 * @var Horde_Mime_Part $p
			 */
			$filename = $p->getName();

			if (!is_null($p->getContentId())) {
				continue;
			}
			if (isset($filename)) {
				// do not show technical attachments
				if (in_array($filename, $this->attachmentsToIgnore)) {
					continue;
				} else {
					return true;
				}
			}
			if ($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function loadMessageBodies() {
		$headers = [];

		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->structure();
		$fetch_query->flags();
		$fetch_query->size();
		$fetch_query->imapDate();

		$headers = array_merge($headers, [
			'importance',
			'list-post',
			'x-priority'
		]);
		$headers[] = 'content-type';

		$fetch_query->headers('imp', $headers, [
			'cache' => true,
			'peek' => true
		]);

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
				$partId = $structure->findBody();
				$this->getPart($structure->getPart($partId), $partId);
			}
		}
	}

	/**
	 * @param $p \Horde_Mime_Part
	 * @param $partNo
	 */
	private function getPart($p, $partNo) {
		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		$filename = $p->getName();
		if (isset($filename)) {
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
	 * @param string $specialRole
	 * @return array
	 */
	public function getFullMessage($specialRole = null) {
		$mailBody = $this->plainMessage;

		$data = $this->jsonSerialize();
		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
		} else {
			$mailBody = $this->htmlService->convertLinks($mailBody);
			list($mailBody, $signature) = $this->htmlService->parseMailBody($mailBody);
			$data['body'] = $specialRole === 'drafts' ? $mailBody : nl2br($mailBody);
			$data['signature'] = $signature;
		}

		$data['attachments'] = $this->attachments;

		return $data;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getUid(),
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'cc' => $this->getCC()->jsonSerialize(),
			'bcc' => $this->getBCC()->jsonSerialize(),
			'fromEmail' => is_null($this->getFrom()->first()) ? null : $this->getFrom()->first()->getEmail(),
			'subject' => $this->getSubject(),
			'date' => OC::$server->getDateTimeFormatter()->formatDate($this->getSentDate()->format('U')),
			'dateInt' => $this->getSentDate()->getTimestamp(),
			'dateIso' => $this->getSentDate()->format('c'),
			'size' => Util::humanFileSize($this->getSize()),
			'flags' => $this->getFlags(),
		];
	}

	/**
	 * @param int     $accountId
	 * @param string  $folderId
	 * @param int     $messageId
	 * @param Closure $attachments
	 * @return string
	 */
	public function getHtmlBody($accountId, $folderId, $messageId, Closure $attachments) {
		return $this->htmlService->sanitizeHtmlMailBody($this->htmlMessage, [
				'accountId' => $accountId,
				'folderId' => $folderId,
				'messageId' => $messageId,
				], $attachments);
	}

	/**
	 * @return string
	 */
	public function getPlainBody() {
		return $this->plainMessage;
	}

	/**
	 * @param Horde_Mime_Part $part
	 * @param int $partNo
	 */
	private function handleMultiPartMessage($part, $partNo) {
		$i = 1;
		foreach ($part->getParts() as $p) {
			$this->getPart($p, "$partNo.$i");
			$i++;
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param int $partNo
	 */
	private function handleTextMessage($p, $partNo) {
		$data = $this->loadBodyData($p, $partNo);
		$this->plainMessage .= trim($data) . "\n\n";
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param int $partNo
	 */
	private function handleHtmlMessage($p, $partNo) {
		$this->hasHtmlMessage = true;
		if ($this->loadHtmlMessage) {
			$data = $this->loadBodyData($p, $partNo);
			$this->htmlMessage .= $data . "<br><br>";
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param int $partNo
	 * @return string
	 * @throws DoesNotExistException
	 * @throws \Exception
	 */
	private function loadBodyData(Horde_Mime_Part $p, $partNo) {
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

	public function getContent() {
		return $this->getPlainBody();
	}

	public function setContent($content) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return array
	 */
	public function getCloudAttachments() {
		throw new Exception('not implemented');
	}

	/**
	 * @return int[]
	 */
	public function getLocalAttachments() {
		throw new Exception('not implemented');
	}

	/**
	 * @param File $file
	 */
	public function addAttachmentFromFiles(File $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return IMessage
	 */
	public function getRepliedMessage() {
		throw new Exception('not implemented');
	}

	/**
	 * @param IMessage $message
	 */
	public function setRepliedMessage(IMessage $message) {
		throw new Exception('not implemented');
	}

}
