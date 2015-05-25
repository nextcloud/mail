<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2012, 2013 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail;

use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use OCA\Mail\Service\Html;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Util;

class Message {

	/**
	 * @var string[]
	 */
	private $attachmentsToIgnore = ['signature.asc', 'smime.p7s'];

	/**
	 * @param \Horde_Imap_Client_Socket|null $conn
	 * @param \Horde_Imap_Client_Mailbox $mailBox
	 * @param integer $messageId
	 * @param \Horde_Imap_Client_Data_Fetch|null $fetch
	 * @param boolean $loadHtmlMessage
	 */
	public function __construct($conn, $mailBox, $messageId, $fetch=null,
		$loadHtmlMessage=false) {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
		$this->messageId = $messageId;
		$this->loadHtmlMessage = $loadHtmlMessage;

		// TODO: inject ???
//		$cacheDir = \OC::$server->getUserFolder() . '/mail/html-cache';
//		$this->htmlService = new Html($cacheDir);
		$this->htmlService = new Html();

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
	 * @var \Horde_Imap_Client_Socket
	 */
	private $conn;

	/**
	 * @var \Horde_Imap_Client_Mailbox
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
		return $this->fetch->getUid();
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
	 * @return \Horde_Imap_Client_Data_Envelope
	 */
	public function getEnvelope() {
		return $this->fetch->getEnvelope();
	}

	/**
	 * @return string
	 */
	public function getFromEmail() {
		$e = $this->getEnvelope();
		$from = $e->from[0];
		return $from ? $from->bare_address : null;
	}

	/**
	 * @return string
	 */
	public function getFrom() {
		$e = $this->getEnvelope();
		$from = $e->from[0];
		return $from ? $from->label : null;
	}

	/**
	 * @return string
	 */
	public function getToEmail() {
		$e = $this->getEnvelope();
		$to = $e->to[0];
		return $to ? $to->bare_address : null;
	}

	public function getTo() {
		$e = $this->getEnvelope();
		$to = $e->to[0];
		return $to ? $to->label : null;
	}

	/**
	 * @return array
	 */
	public function getToList() {
		$e = $this->getEnvelope();
		return $this->convertAddressList($e->to);
	}

	public function getCCList() {
		$e = $this->getEnvelope();
		return $this->convertAddressList($e->cc);
	}

	public function getBCList() {
		$e = $this->getEnvelope();
		return $this->convertAddressList($e->bcc);
	}

	public function getReplyToList() {
		$e = $this->getEnvelope();
		return $this->convertAddressList($e->from);
	}

	// on reply, fill cc with everyone from to and cc except yourself

	/**
	 * @param string $ownMail
	 */
	public function getReplyCcList($ownMail) {
		$e = $this->getEnvelope();
		$list = new \Horde_Mail_Rfc822_List();
		$list->add($e->to);
		$list->add($e->cc);
		$list->unique();
		$list->remove($ownMail);
		return $this->convertAddressList($list);
	}

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
	 * @return \Horde_Imap_Client_DateTime
	 */
	public function getSentDate() {
		return $this->fetch->getImapDate();
	}

	public function getSize() {
		return $this->fetch->getSize();
	}

	/**
	 * @param \Horde_Mime_Part $part
	 * @return bool
	 */
	private function hasAttachments($part) {
		foreach($part->getParts() as $p) {
			/**
			 * @var \Horde_Mime_Part $p
			 */
			$filename = $p->getName();

			if(!is_null($p->getContentId())) {
				continue;
			}
			if(isset($filename)) {
				// do not show technical attachments
				if(in_array($filename, $this->attachmentsToIgnore)) {
					continue;
				} else {
					return true;
				}
			}
			if($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function loadMessageBodies() {
		$headers = [];

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
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
			'cache' => true
		]);

		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$ids = new \Horde_Imap_Client_Ids($this->messageId);
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
		if ($structure_type == 'multipart') {
			$i = 1;
			foreach($structure->getParts() as $p) {
				$this->getPart($p, $i++);
			}
		} else {
			if ($structure->findBody() != null) {
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
		if(isset($filename)) {
			if(in_array($filename, $this->attachmentsToIgnore)) {
				return;
			}
			$this->attachments[]= [
				'id' => $p->getMimeId(),
				'messageId' => $this->messageId,
				'fileName' => $filename,
				'mime' => $p->getType(),
				'size' => $p->getBytes()
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
		if ($p[0]=='message') {
			$data = $this->loadBodyData($p, $partNo);
			$this->plainMessage .= trim($data) ."\n\n";
		}
	}

	/**
	 * @param string $ownMail
	 * @param string $specialRole
	 */
	public function getFullMessage($ownMail, $specialRole=null) {
		$mailBody = $this->plainMessage;

		$data = $this->getListArray();
		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
		} else {
			$mailBody = $this->htmlService->convertLinks($mailBody);
			list($mailBody, $signature) = $this->htmlService->parseMailBody($mailBody);
			$data['body'] = $specialRole === 'drafts' ? $mailBody : nl2br($mailBody);
			$data['signature'] = $signature;
		}

		if (count($this->attachments) === 1) {
			$data['attachment'] = $this->attachments[0];
		}
		if (count($this->attachments) > 1) {
			$data['attachments'] = $this->attachments;
		}

		if ($specialRole === 'sent') {
			$data['replyToList'] = $this->getToList();
			$data['replyCcList'] = $this->getCCList();
		} else {
			$data['replyToList'] = $this->getReplyToList();
			$data['replyCcList'] = $this->getReplyCcList($ownMail);
		}
		return $data;
	}

	public function getListArray() {
		$data = [];
		$data['id'] = $this->getUid();
		$data['from'] = $this->getFrom();
		$data['fromEmail'] = $this->getFromEmail();
		$data['to'] = $this->getTo();
		$data['toEmail'] = $this->getToEmail();
		$data['toList'] = $this->getToList();
		$data['subject'] = $this->getSubject();
		$data['date'] = Util::formatDate($this->getSentDate()->format('U'));
		$data['size'] = Util::humanFileSize($this->getSize());
		$data['flags'] = $this->getFlags();
		$data['dateInt'] = $this->getSentDate()->getTimestamp();
		$data['dateIso'] = $this->getSentDate()->format('c');
		$data['ccList'] = $this->getCCList();
		return $data;
	}

	public function getHtmlBody() {
		return $this->htmlService->sanitizeHtmlMailBody($this->htmlMessage);
	}

	/**
	 * @param \Horde_Mime_Part $part
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
	 * @param \Horde_Mime_Part $p
	 * @param int $partNo
	 */
	private function handleTextMessage($p, $partNo) {
		$data = $this->loadBodyData($p, $partNo);
		$data = Util::sanitizeHTML($data);
		$this->plainMessage .= trim($data) ."\n\n";
	}

	/**
	 * @param \Horde_Mime_Part $p
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
	 * @param \Horde_Mime_Part $p
	 * @param int $partNo
	 * @return string
	 * @throws DoesNotExistException
	 * @throws \Exception
	 */
	private function loadBodyData($p, $partNo) {
		// DECODE DATA
		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$ids = new \Horde_Imap_Client_Ids($this->messageId);

		$fetch_query->bodyPart($partNo);
		$fetch_query->bodyPartSize($partNo);
		$fetch_query->mimeHeader($partNo);

		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		/** @var $fetch \Horde_Imap_Client_Data_Fetch */
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

		$data = iconv($p->getCharset(), 'utf-8', $data);
		return $data;
	}

	/**
	 * @param \Horde_Imap_Client_Data_Envelope $envelope
	 * @return array
	 */
	private function convertAddressList($envelope) {
		$list = [];
		foreach ($envelope as $t) {
			$list[] = [
				'label' => $t->label,
				'email' => $t->bare_address
			];
		}
		return $list;
	}

}
