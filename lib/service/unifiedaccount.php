<?php

namespace OCA\Mail\Service;

class UnifiedAccount implements IAccount {

	const ID = -1;

	/** @var AccountService */
	private $accountService;

	public function __construct(AccountService $accountService, $userId) {
		$this->accountService = $accountService;
		$this->userId = $userId;
	}
	/**
	 * @return array
	 */
	public function getConfiguration() {
		return [
			'accountId' => UnifiedAccount::ID,
		];
	}

	/**
	 * @return array
	 * TODO: function name is :hankey:
	 */
	public function getListArray() {
		$inbox = $this->buildInbox();
		return [
			'id'             => UnifiedAccount::ID,
			'email'          => '',
			'folders'        => [$inbox],
			'specialFolders' => [],
			'delimiter' => '.',
		];
	}

	private function buildInbox() {
		$displayName = 'All inboxes';
		$id = 'all-inboxes';

		$allAccounts = $this->accountService->findByUserId($this->userId);
		$unreadCounts = array_map(function($account) {
			/** @var IAccount $account */
			$inbox = $account->getInbox();
			if (is_null($inbox)) {
				return 0;
			}
			$status = $inbox->getStatus();
			return isset($status['unseen']) ? $status['unseen'] : 0;
		}, $allAccounts);

		$unseen = array_sum($unreadCounts);
		return [
			'id' => base64_encode($id),
			'parent' => null,
			'name' => $displayName,
			'specialRole' => 'inbox',
			'unseen' => $unseen,
			'total' => 100,
			'isEmpty' => false,
			'accountId' => UnifiedAccount::ID,
			'noSelect' => false,
			'uidvalidity' => 0,
			'uidnext' => 0,
			'delimiter' => '.'
		];	}

	/**
	 * @param $folderId
	 * @return IMailBox
	 */
	public function getMailbox($folderId) {
		return new UnifiedMailbox($this->accountService, $this->userId);
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return '';
	}

	/**
	 * @param string $folderId
	 * @param int $messageId
	 */
	public function deleteMessage($folderId, $messageId) {
		// TODO: Implement deleteMessage() method.
	}

	/**
	 * @param string[] $query
	 * @return array
	 */
	public function getChangedMailboxes($query) {
		return [];
	}

	/**
	 * @return IMailBox
	 */
	public function getInbox() {
		return null;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return UnifiedAccount::ID;
	}
}
