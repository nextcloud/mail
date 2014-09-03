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
use OCA\Mail\Service\Html;
use OCP\AppFramework\Db\DoesNotExistException;

class Message {

	/**
	 * @param \Horde_Imap_Client_Socket $conn
	 * @param string $folderId
	 * @param integer $messageId
	 * @param \Horde_Imap_Client_Data_Fetch|null $fetch
	 * @param boolean $loadHtmlMessage
	 */
	function __construct($conn, $folderId, $messageId, $fetch=null,
		$loadHtmlMessage=false) {
		$this->conn = $conn;
		$this->folderId = $folderId;
		$this->messageId = $messageId;
		$this->loadHtmlMessage = $loadHtmlMessage;

		// TODO: inject ???
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
	public $attachments = array();
	private $loadHtmlMessage = false;
	private $hasHtmlMessage = false;

	/**
	 * @var \Horde_Imap_Client_Socket
	 */
	private $conn;
	private $folderId;
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
		return array(
			'unseen' => !in_array(Horde_Imap_Client::FLAG_SEEN, $flags),
			'flagged' => in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags),
			'answered' => in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags),
			'deleted' => in_array(Horde_Imap_Client::FLAG_DELETED, $flags),
			'draft' => in_array(Horde_Imap_Client::FLAG_DRAFT, $flags),
			'forwarded' => in_array(Horde_Imap_Client::FLAG_FORWARDED, $flags),
			'hasAttachments' => $this->hasAttachments($this->fetch->getStructure())
		);
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
			if (!is_null($p->getContentId())) {
				continue;
			}
			if (isset($filename)) {
				return true;
			}
			if ($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function loadMessageBodies() {
		$headers = array();

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->structure();
		$fetch_query->flags();
		$fetch_query->size();
		$fetch_query->imapDate();

		$headers = array_merge($headers, array(
			'importance',
			'list-post',
			'x-priority'
		));
		$headers[] = 'content-type';

		$fetch_query->headers('imp', $headers, array(
			'cache' => true,
			'peek'  => true
		));

		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$ids = new \Horde_Imap_Client_Ids($this->messageId);
		$headers = $this->conn->fetch($this->folderId, $fetch_query, array('ids' => $ids));
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
	 * @param integer $partId
	 */
	private function queryBodyPart($partId) {

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$ids = new \Horde_Imap_Client_Ids($this->messageId);

		$fetch_query->bodyPart($partId);
		$headers = $this->conn->fetch($this->folderId, $fetch_query, array('ids' => $ids));
		/** @var $fetch \Horde_Imap_Client_Data_Fetch */
		$fetch = $headers[$this->messageId];
		if (is_null($fetch)) {
			throw new DoesNotExistException("Mail body for this mail($this->messageId) could not be loaded");
		}

		return $fetch->getBodyPart($partId);
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
			$this->attachments[]= array(
				'id' => $p->getMimeId(),
				'fileName' => $filename,
				'mime' => $p->getType(),
				'size' => $p->getBytes()
			);
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

	public function getFullMessage($ownMail) {
		$mailBody = $this->plainMessage;

		$data = $this->getListArray();
		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
		} else {
			$mailBody = $this->htmlService->convertLinks($mailBody);
			list($mailBody, $signature) = $this->htmlService->parseMailBody($mailBody);
			$data['body'] = nl2br($mailBody);
			$data['signature'] = $signature;
		}

		if (count($this->attachments) === 1) {
			$data['attachment'] = $this->attachments[0];
		}
		if (count($this->attachments) > 1) {
			$data['attachments'] = $this->attachments;
		}

		$data['replyToList'] = $this->getReplyToList();
		$data['replyCcList'] = $this->getReplyCcList($ownMail);
		return $data;
	}

	public function getListArray() {
		$data = array();
		$data['id'] = $this->getUid();
		$data['from'] = $this->getFrom();
		$data['fromEmail'] = $this->getFromEmail();
		$data['to'] = $this->getTo();
		$data['toList'] = $this->getToList();
		$data['subject'] = $this->getSubject();
		$data['date'] = \OCP\Util::formatDate($this->getSentDate()->format('U'));
		$data['size'] = \OCP\Util::humanFileSize($this->getSize());
		$data['flags'] = $this->getFlags();
		$data['dateInt'] = $this->getSentDate()->getTimestamp();
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
		$data = \OCP\Util::sanitizeHTML($data);
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
	 */
	private function loadBodyData($p, $partNo) {
		// DECODE DATA
		$data = $this->queryBodyPart($partNo);
		$p->setContents($data);
		$data = $p->toString();

		// decode quotes
		$data = quoted_printable_decode($data);

		//
		// convert the data
		//
		$charset = $p->getCharset();
		if (isset($charset) and $charset !== '') {
			$data = mb_convert_encoding($data, "UTF-8", $charset);
			return $data;
		}
		return $data;
	}

	/**
	 * @param \Horde_Imap_Client_Data_Envelope $envelope
	 * @return array
	 */
	private function convertAddressList($envelope) {
		$list = array();
		foreach ($envelope as $t) {
			$list[] = array(
				'label' => $t->label,
				'email' => $t->bare_address
			);
		}
		return $list;
	}

}
