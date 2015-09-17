<?php

namespace OCA\Mail\Service;

use OCA\Mail\Model\IMessage;

interface IAccount {

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * @return array
	 * TODO: function name is :hankey:
	 */
	public function getListArray();

	/**
	 * @param $folderId
	 * @return IMailbox
	 */
	public function getMailbox($folderId);

	/**
	 * @return string
	 */
	public function getEmail();

	/**
	 * @param IMessage $message
	 * @param int|null $draftUID
	 * @return IMessage
	 */
	public function sendMessage(IMessage $message, $draftUID);

	/**
	 * @param string $folderId
	 * @param int $messageId
	 */
	public function deleteMessage($folderId, $messageId);

	/**
	 * @param string[] $query
	 * @return array
	 */
	public function getChangedMailboxes($query);

	/**
	 * @return IMailBox
	 */
	public function getInbox();

	/**
	 * @return int
	 */
	public function getId();
}
