<?php

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;

class AccountService {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @param MailAccountMapper $mapper
	 */
	public function __construct($mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @param string $currentUserId
	 * @return IAccount[]
	 */
	public function findByUserId($currentUserId) {
		$accounts = $this->mapper->findByUserId($currentUserId);
		$accounts = array_map(function($a) {
			return new Account($a);
		}, $accounts);
		if (count($accounts) > 1) {
			$unifiedAccount = $this->buildUnifiedAccount($currentUserId);
			$accounts = array_merge([$unifiedAccount], $accounts);
		}

		return $accounts;
	}

	/**
	 * @param $currentUserId
	 * @param $accountId
	 * @return IAccount
	 */
	public function find($currentUserId, $accountId) {
		if ((int)$accountId === UnifiedAccount::ID) {
			return $this->buildUnifiedAccount($currentUserId);
		}
		return new Account($this->mapper->find($currentUserId, $accountId));
	}

	/**
	 * @param int $accountId
	 */
	public function delete($currentUserId, $accountId) {
		if ((int)$accountId === UnifiedAccount::ID) {
			return;
		}
		$mailAccount = $this->mapper->find($currentUserId, $accountId);
		$this->mapper->delete($mailAccount);
	}

	/**
	 * @param $newAccount
	 * @return \OCA\Mail\Db\MailAccount
	 */
	public function save($newAccount) {
		return $this->mapper->save($newAccount);
	}

	private function buildUnifiedAccount($userId) {
		return new UnifiedAccount($this, $userId);
	}
}
