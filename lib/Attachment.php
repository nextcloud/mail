<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Mail;

use Horde_Imap_Client_Data_Fetch;
use OCP\AppFramework\Db\DoesNotExistException;

class Attachment {
	/**
	 * @param \Horde_Imap_Client_Socket $conn
	 * @param \Horde_Imap_Client_Mailbox $mailBox
	 * @param int $messageUid
	 * @param string $attachmentId
	 */
	public function __construct($conn, $mailBox, $messageUid, $attachmentId) {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
		$this->messageUid = $messageUid;
		$this->attachmentId = $attachmentId;

		$this->load();
	}

	/**
	 * @var \Horde_Imap_Client_Socket
	 */
	private $conn;

	/**
	 * @var \Horde_Imap_Client_Mailbox
	 */
	private $mailBox;
	private $messageUid;
	private $attachmentId;

	/**
	 * @var \Horde_Mime_Part
	 */
	private $mimePart;

	private function load(): void {
		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$fetch_query->bodyPart($this->attachmentId);
		$fetch_query->mimeHeader($this->attachmentId);

		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$ids = new \Horde_Imap_Client_Ids($this->messageUid);
		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		if (!isset($headers[$this->messageUid])) {
			throw new DoesNotExistException('Unable to load the attachment.');
		}
		/** @var Horde_Imap_Client_Data_Fetch $fetch */
		$fetch = $headers[$this->messageUid];
		/** @var \Horde_Mime_Headers $mimeHeaders */
		$mimeHeaders = $fetch->getMimeHeader($this->attachmentId, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);

		$this->mimePart = new \Horde_Mime_Part();

		// Serve all files with a content-disposition of "attachment" to prevent Cross-Site Scripting
		$this->mimePart->setDisposition('attachment');

		// Extract headers from part
		$contentDisposition = $mimeHeaders->getValue('content-disposition', \Horde_Mime_Headers::VALUE_PARAMS);
		if (!is_null($contentDisposition) && isset($contentDisposition['filename'])) {
			$this->mimePart->setDispositionParameter('filename', $contentDisposition['filename']);
		} else {
			$contentDisposition = $mimeHeaders->getValue('content-type', \Horde_Mime_Headers::VALUE_PARAMS);
			if (isset($contentDisposition['name'])) {
				$this->mimePart->setContentTypeParameter('name', $contentDisposition['name']);
			}
		}

		/* Content transfer encoding. */
		if ($tmp = $mimeHeaders->getValue('content-transfer-encoding')) {
			$this->mimePart->setTransferEncoding($tmp);
		}

		/* Content type */
		if (strstr($mimeHeaders->getValue('content-type'), 'text/calendar')) {
			$this->mimePart->setType('text/calendar');
			if ($this->mimePart->getContentTypeParameter('name') === null) {
				$this->mimePart->setContentTypeParameter('name', 'calendar.ics');
			}
		} else {
			// To prevent potential problems with the SOP we serve all files but calendar entries with the
			// MIME type "application/octet-stream"
			$this->mimePart->setType('application/octet-stream');
		}

		$body = $fetch->getBodyPart($this->attachmentId);
		$this->mimePart->setContents($body);
	}

	/**
	 * @return string
	 */
	public function getContents() {
		return $this->mimePart->getContents();
	}

	/**
	 * @return string|null
	 */
	public function getName() {
		return $this->mimePart->getName();
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->mimePart->getType();
	}
}
