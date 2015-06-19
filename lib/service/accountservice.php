<?php

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCP\IL10N;

class AccountService {

	/** @var \OCA\Mail\Db\MailAccountMapper */
	private $mapper;

	/** @var IL10N */
	private $l10n;

	/**
	 * @param MailAccountMapper $mapper
	 */
	public function __construct(MailAccountMapper $mapper, IL10N $l10n) {
		$this->mapper = $mapper;
		$this->l10n = $l10n;
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
		return new UnifiedAccount($this, $userId, $this->l10n);
	}
}
