<?php

namespace OCA\Mail\Service;

use OCA\Mail\Attachment;
use OCA\Mail\Message;

interface IMailBox {

	/**
	 * @return string
	 */
	public function getFolderId();

	/**
	 * @param int $from
	 * @param int $count
	 * @param string|\Horde_Imap_Client_Search_Query $filter
	 * @return array
	 */
	public function getMessages($from, $count, $filter);

	/**
	 * @return string
	 */
	public function getSpecialRole();

	/**
	 * @param int $id
	 * @return Message
	 */
	public function getMessage($id);

	/**
	 * @param int $messageId
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getAttachment($messageId, $attachmentId);

	/**
	 * @param int $messageId
	 * @param string $flag
	 * @param mixed $value
	 */
	public function setMessageFlag($messageId, $flag, $value);

	/**
	 * @return array
	 */
	public function getStatus();
}