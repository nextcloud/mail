<?php

namespace OCA\Mail\Service;

use OCP\IL10N;

class UnifiedAccount implements IAccount {

	const ID = -1;

	/** @var AccountService */
	private $accountService;

	/** @var IL10N */
	private $l10n;

	/**
	 * @param AccountService $accountService
	 * @param string $userId
	 * @param IL10N $l10n
	 */
	public function __construct(AccountService $accountService, $userId, IL10N $l10n) {
		$this->accountService = $accountService;
		$this->userId = $userId;
		$this->l10n = $l10n;
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
		$displayName = (string)$this->l10n->t('All inboxes');
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
	 * @param string $messageId
	 */
	public function deleteMessage($folderId, $messageId) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		$inbox = $account->getInbox();
		$messageId = $data[1];

		$account->deleteMessage($inbox->getFolderId(), $messageId);
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

	/**
	 * @param $messageId
	 * @return array
	 */
	public function resolve($messageId) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		$inbox = $account->getInbox();
		$messageId = $data[1];

		return [$account, base64_encode($inbox->getFolderId()), $messageId];
	}
}
