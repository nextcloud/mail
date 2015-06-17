<?php

namespace OCA\Mail\Service;

use OCA\Mail\Attachment;
use OCA\Mail\Message;

class UnifiedMailbox implements IMailBox {

	public function __construct(AccountService $accountService, $userId) {
		$this->accountService = $accountService;
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getFolderId() {
		// TODO: Implement getFolderId() method.
	}

	/**
	 * @param int $from
	 * @param int $count
	 * @param string|\Horde_Imap_Client_Search_Query $filter
	 * @return array
	 */
	public function getMessages($from, $count, $filter) {
		$allAccounts = $this->accountService->findByUserId($this->userId);
		$allMessages = array_map(function($account) use ($from, $count, $filter) {
			/** @var IAccount $account */
			if ($account->getId() === UnifiedAccount::ID) {
				return [];
			}
			$inbox = $account->getInbox();
			if (is_null($inbox)) {
				return [];
			}

			$messages = $inbox->getMessages($from, $count, $filter);
			$messages = array_map(function($message) use ($account) {
				$message['id'] = base64_encode(json_encode([$account->getId(), $message['id']]));
				$message['accountMail'] = $account->getEmail();
				return $message;
			}, $messages);

			return $messages;
		}, $allAccounts);

		$allMessages = array_reduce($allMessages, function($a, $b) {
			if (is_null($a)) {
				return $b;
			}
			if (is_null($b)) {
				return $a;
			}
			return array_merge($a, $b);
		});

		// sort by time
		usort($messages, function($a, $b) {
			return $a['dateInt'] < $b['dateInt'];
		});

		return $allMessages;
	}

	/**
	 * @return string
	 */
	public function getSpecialRole() {
		return 'inbox';
	}

	/**
	 * @param string $messageId
	 * @return Message
	 */
	public function getMessage($messageId, $loadHtmlMessageBody = false) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		return $account->getInbox()->getMessage($data[1], $loadHtmlMessageBody);
	}

	/**
	 * @param int $messageId
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getAttachment($messageId, $attachmentId) {
		// TODO: Implement getAttachment() method.
	}

	/**
	 * @param int $messageId
	 * @param string $flag
	 * @param mixed $value
	 */
	public function setMessageFlag($messageId, $flag, $value) {
		// TODO: Implement setMessageFlag() method.
	}

	/**
	 * @return array
	 */
	public function getStatus() {
		// TODO: Implement getStatus() method.
	}
}
