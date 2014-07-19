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

class Message {

	/**
	 * @param $conn
	 * @param $folderId
	 * @param $messageId
	 * @param null $fetch
	 */
	function __construct($conn, $folderId, $messageId, $fetch=null) {
		$this->conn = $conn;
		$this->folderId = $folderId;
		$this->messageId = $messageId;

		if ($fetch === null) {
			$this->loadMessageBodies();
		} else {
			$this->fetch = $fetch;
		}
	}

	// output all the following:
	// the message may in $htmlmsg, $plainmsg, or both
	public $header = null;
	public $htmlMessage = '';
	public $plainMessage = '';
	public $charset = '';
	public $attachments = array();

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

	public function getUid() {
		return $this->fetch->getUid();
	}

	public function getFlags() {
		$flags = $this->fetch->getFlags();
		return array('unseen' => !in_array("\seen", $flags));
	}

	public function getEnvelope() {
		return $this->fetch->getEnvelope();
	}

	public function getFromEmail() {
		$e = $this->getEnvelope();
		$from = $e->from[0];
		return $from->bare_address;
	}

	public function getFrom() {
		$e = $this->getEnvelope();
		$from = $e->from[0];
		return $from->label;
	}

	public function getToEmail() {
		$e = $this->getEnvelope();
		$from = $e->to[0];
		return $from->bare_address;
	}

	public function getTo() {
		$e = $this->getEnvelope();
		$to = $e->to[0];
		return $to ? $to->label : null;
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
		// TODO: Use internal imap date for now
		return $this->fetch->getImapDate();
	}

	public function getSize() {
		return $this->fetch->getSize();
	}

	private function loadMessageBodies() {
		$headers = array();

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->structure();
		$fetch_query->flags();
		$fetch_query->seq();
		$fetch_query->size();
		$fetch_query->uid();
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

	private function queryBodyPart($partId) {

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$ids = new \Horde_Imap_Client_Ids($this->messageId);

		$fetch_query->bodyPart($partId);
		$headers = $this->conn->fetch($this->folderId, $fetch_query, array('ids' => $ids));
		/** @var $fetch \Horde_Imap_Client_Data_Fetch */
		$fetch = $headers[$this->messageId];

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
			//
			// TODO: decode necessary ???
			//
//			$filename = OC_SimpleMail_Helper::decode($filename);

			// for now we just keep the size - we need a new function to download an attachment
			// this is a problem if two files have same name
			$this->attachments[$filename] = $p->getBytes();
			return;
		}

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
		if (isset( $charset) and $charset !== '') {
			$data = mb_convert_encoding($data, "UTF-8", $charset);
		}

		//
		// sanitize
		//
		$data = \OCP\Util::sanitizeHTML($data);
		//
		// link detection
		//
		$data = preg_replace('!(http)(s)?:\/\/[a-zA-Z0-9.?&_/]+!', "<a href=\"\\0\" target=\"_blank\">\\0</a>", $data);

		// TEXT
		if ($p->getPrimaryType() == 'text' && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if ($p->getSubType() == 'plain') {
				$this->plainMessage .= trim($data) ."\n\n";
			} else {
				$this->htmlMessage .= $data ."<br><br>";
				$this->charset = $charset;  // assume all parts are same charset
			}
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p[0]=='message' && $data) {
			$this->plainMessage .= trim($data) ."\n\n";
		}

		//
		// TODO: is recursion necessary???
		//

		// SUBPART RECURSION
//		if ($p->parts) {
//			foreach ($p->parts as $partno0=>$p2)
//			$this->getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
//		}
	}

	private function getAttachmentInfo() {
		$attachment_info = array();
		foreach ($this->attachments as $filename => $data) {
			// TODO: mime-type ???
			array_push($attachment_info, array("filename" => $filename, "size" => $data));
		}

		return $attachment_info;
	}

	public function as_array() {
		$mail_body = $this->plainMessage;
		$mail_body = nl2br($mail_body);

		if (empty($this->plainMessage) && !empty($this->htmlMessage)) {
			$mail_body = "<br/><h2>Only Html body available!</h2><br/>";
		}

		$data = $this->getListArray();
		$data['body'] = $mail_body;
		$data['attachments'] = $this->getAttachmentInfo();
		return $data;
	}

	public function getListArray() {
		$data = array();
		$data['id'] = $this->getUid();
		$data['from'] = $this->getFrom();
		$data['to'] = $this->getTo();
		$data['subject'] = $this->getSubject();
		$data['date'] = \OCP\Util::formatDate($this->getSentDate()->format('U'));
		$data['size'] = \OCP\Util::humanFileSize($this->getSize());
		$data['flags'] = $this->getFlags();
		$data['dateInt'] = $this->getSentDate()->getTimestamp();
		return $data;
	}
}
