<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
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
	 * @param $folder_id
	 * @param $message_id
	 */
	function __construct($conn, $folder_id, $message_id) {
		$this->conn = $conn;
		$this->folder_id = $folder_id;
		$this->message_id = $message_id;
	}

	// output all the following:
	// the message may in $htmlmsg, $plainmsg, or both
	public $header = null;
	public $htmlmsg = '';
	public $plainmsg = '';
	public $charset = '';
	public $attachments = array();

	private $conn, $folder_id, $message_id;
	private $fetch;

	public function setInfo($info) {
		$this->fetch = $info;
	}

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

	public function getTo() {
		$e = $this->getEnvelope();
		$to = $e->to[0];
		return $to ? $to->label : null;
	}

	public function getSubject() {
		$e = $this->getEnvelope();
		return $e->subject;
	}

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
		$ids = new \Horde_Imap_Client_Ids($this->message_id);
		$headers = $this->conn->fetch($this->folder_id, $fetch_query, array('ids' => $ids));
		$fetch = $headers[$this->message_id];

		// set $this->fetch to get to, from ...
		$this->fetch = $fetch;

		// analyse the body part
		$structure = $fetch->getStructure();

		// debugging below
		$structure_type = $structure->getPrimaryType();
		if ($structure_type == 'multipart') {
			$i = 1;
			foreach($structure->getParts() as $p) {
				$this->getpart($p, $i++);
			}
		} else {
			if ($structure->findBody() != null) {
				// get the body from the server
				$partId = $structure->findBody();
				$this->queryBodyPart($partId);
			}
		}
	}

	private function queryBodyPart($partId) {

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$ids = new \Horde_Imap_Client_Ids($this->message_id);

		$bodypart_params = array('decode' => true);

		$fetch_query->bodyPart($partId, $bodypart_params);
		$headers = $this->conn->fetch($this->folder_id, $fetch_query, array('ids' => $ids));
		$fetch = $headers[$this->message_id];

		return $fetch->getBodyPart($partId);
	}

	private function getpart($p, $partno) {

		// $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart

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
		$data = $this->queryBodyPart($partno);

		// Any part may be encoded, even plain text messages, so check everything.
//		if (strtolower($p)=='quoted_printable') {
//			$data = quoted_printable_decode($data);
//		}
//		if (strtolower($p[5])=='base64') {
//			$data = base64_decode($data);
//		}
		// no need to decode 7-bit, 8-bit, or binary

		//
		// convert the data
		//
		$charset = $p->getCharset();
		if (isset( $charset)) {
			$data = mb_convert_encoding($data, "UTF-8", $charset);
		}

		// TEXT
		if ($p->getPrimaryType() == 'text' && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if ($p->getSubType() == 'plain') {
				$this->plainmsg .= trim($data) ."\n\n";
			} else {
				$this->htmlmsg .= $data ."<br><br>";
				$this->charset = $charset;  // assume all parts are same charset
			}
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p[0]=='message' && $data) {
			$this->plainmsg .= trim($data) ."\n\n";
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

	private function get_attachment_info() {
		$attachment_info = array();
		foreach ($this->attachments as $filename => $data) {
			// TODO: mime-type ???
			array_push($attachment_info, array("filename" => $filename, "size" => $data));
		}

		return $attachment_info;
	}

	public function as_array() {
		$this->loadMessageBodies();
		$mail_body = $this->plainmsg;
		$mail_body = nl2br($mail_body);

		if (empty($this->plainmsg) && !empty($this->htmlmsg)) {
			$mail_body = "<br/><h2>Only Html body available!</h2><br/>";
		}

		$data = $this->getListArray();
		$data['body'] = $mail_body;
		$data['attachments'] = $this->get_attachment_info();
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
		return $data;
	}
}
