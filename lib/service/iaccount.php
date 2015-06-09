<?php

namespace OCA\Mail\Service;


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
