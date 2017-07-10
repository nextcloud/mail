<?php

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

namespace OCA\Mail\Model;

use Horde_Mail_Rfc822_List;
use OCA\Mail\Account;
use OCA\Mail\Service\IAccount;

/**
 * Simple data class that wraps the request data of a new message or reply
 */
class NewMessageData {

	/** @var Account */
	private $account;

	/** @var Horde_Mail_Rfc822_List */
	private $to;

	/** @var Horde_Mail_Rfc822_List */
	private $cc;

	/** @var Horde_Mail_Rfc822_List */
	private $bcc;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	/** @var array */
	private $attachments;

	/**
	 * @param Account $account
	 * @param Horde_Mail_Rfc822_List $to
	 * @param Horde_Mail_Rfc822_List $cc
	 * @param Horde_Mail_Rfc822_List $bcc
	 * @param string $subject
	 * @param string $body
	 * @param array $attachments
	 */
	public function __construct(Account $account, Horde_Mail_Rfc822_List $to, Horde_Mail_Rfc822_List $cc,
		Horde_Mail_Rfc822_List $bcc, $subject, $body, array $attachments) {
		$this->account = $account;
		$this->to = $to;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->subject = $subject;
		$this->body = $body;
		$this->attachments = $attachments;
	}

	public static function fromRequest(IAccount $account, $to, $cc, $bcc, $subject, $body, $attachments) {
		$toArray = is_null($to) ? [] : Message::parseAddressList($to);
		$ccArray = is_null($cc) ? [] : Message::parseAddressList($cc);
		$bccArray = is_null($bcc) ? [] : Message::parseAddressList($bcc);
		$attchmentsArray = is_null($attachments) ? [] : $attachments;

		return new NewMessageData($account, $toArray, $ccArray, $bccArray, $subject, $body, $attchmentsArray);
	}

	/**
	 * @return Account
	 */
	public function getAccount() {
		return $this->account;
	}

	/**
	 * @return Horde_Mail_Rfc822_List
	 */
	public function getTo() {
		return $this->to;
	}

	/**
	 * @return Horde_Mail_Rfc822_List
	 */
	public function getCc() {
		return $this->cc;
	}

	/**
	 * @return Horde_Mail_Rfc822_List
	 */
	public function getBcc() {
		return $this->bcc;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @return array
	 */
	public function getAttachments() {
		return $this->attachments;
	}

}
