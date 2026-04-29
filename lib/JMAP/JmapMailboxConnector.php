<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class JmapMailboxConnector implements IMailboxConnector {
	use TTransactional;

	private const MAILBOX_SYNC_TTL = 300;
	private const DELIMITER = '/';

	private JmapClient $client;

	public function __construct(
		private readonly JmapOperationsService $jmapOperationsService,
		private MailboxMapper $mailboxMapper,
		private MailAccountMapper $mailAccountMapper,
		private ITimeFactory $timeFactory,
		private IEventDispatcher $dispatcher,
		private IDBConnection $dbConnection,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAccount(Account $account, bool $force = false): void {
		if (!$force && $account->getMailAccount()->getLastMailboxSync() >= ($this->timeFactory->getTime() - self::MAILBOX_SYNC_TTL)) {
			$this->logger->debug('account is up to date, skipping mailbox sync');
			return;
		}

		$this->jmapOperationsService->connect($account);
		$remoteMailboxes = $this->jmapOperationsService->collectionList();
		$localMailboxes = $this->mailboxMapper->findAll($account);
		$remoteMailboxNames = $this->constructMailboxSyncNameLookup($remoteMailboxes, $this->logger);

		// create or update mailboxes locally that are present remotely
		foreach ($remoteMailboxes as $remoteMailbox) {
			$remoteMailboxName = $remoteMailboxNames[$remoteMailbox->getRemoteId()] ?? $remoteMailbox->getName();
			$remoteMailbox->setName($remoteMailboxName);
			$remoteMailbox->setNameHash(md5($remoteMailboxName));

			$localMailboxIdx = null;
			$localMailboxData = null;
			foreach ($localMailboxes as $key => $candidate) {
				if ($candidate->getRemoteId() === $remoteMailbox->getRemoteId()) {
					$localMailboxIdx = $key;
					$localMailboxData = $candidate;
					break;
				}
			}

			if ($localMailboxData === null) {
				$remoteMailbox->setAccountId($account->getId());
				$this->mailboxMapper->insert($remoteMailbox);
			} else {
				$localMailbox = $this->mergeMailbox($localMailboxData, $remoteMailbox);
				$this->mailboxMapper->update($localMailbox);
				unset($localMailboxes[$localMailboxIdx]);
			}
		}
		// delete local mailboxes that are not present remotely
		if (count($localMailboxes) > 0) {
			foreach ($localMailboxes as $mailbox) {
				$this->mailboxMapper->delete($mailbox);
			}
		}

		$this->dispatcher->dispatchTyped(new MailboxesSynchronizedEvent($account));
	}

	#[\Override]
	public function syncMailbox(Account $account, Mailbox $mailbox): void {
		if ($mailbox->getRemoteId() === null) {
			throw new ServiceException('JMAP mailbox is missing a remote id');
		}

		$this->jmapOperationsService->connect($account);
		
		$remoteMailbox = $this->jmapOperationsService->collectionFetch($mailbox->getRemoteId());
		$this->mailboxMapper->update($this->mergeMailbox($mailbox, $remoteMailbox, ['name', 'nameHash']));
	}

	#[\Override]
	public function createMailbox(Account $account, string $name, array $specialUse = []): Mailbox {
		$this->jmapOperationsService->connect($account);
		
		// extract the mailbox name and parent name from the full path for remote operation
		$pathParts = explode(self::DELIMITER, $name);
		if (count($pathParts) === 1) {
			$mailboxName = $name;
			$parentName = null;
		} else {
			$mailboxName = array_pop($pathParts);
			$parentName = implode(self::DELIMITER, $pathParts);
		}
		// find the parent mailbox to retrieve remote mailbox id for remote operation
		if ($parentName !== null) {
			try {
				$location = $this->mailboxMapper->findByName($account, $parentName);
			} catch (DoesNotExistException $e) {
				throw new ServiceException('JMAP parent mailbox does not exist: ' . $parentName);
			}

			if ($location->getRemoteId() === null) {
				throw new ServiceException('JMAP parent mailbox is missing a remote id: ' . $parentName);
			}
		} else {
			$location = null;
		}
		// construct the mailbox for the remote and local creation
		$mailbox = new Mailbox();
		$mailbox->setAccountId($account->getId());
		$mailbox->setDelimiter(self::DELIMITER);
		$mailbox->setMessages(0);
		$mailbox->setUnseen(0);
		$mailbox->setSelectable(true);
		$mailbox->setAttributes(json_encode(['\\subscribed'], JSON_THROW_ON_ERROR));
		$mailbox->setSpecialUse(json_encode($specialUse, JSON_THROW_ON_ERROR));
		// create in remote store, using only the mailbox
		$mailbox->setName($mailboxName);
		$mailbox = $this->jmapOperationsService->collectionCreate($location, $mailbox);
		if ($mailbox === null) {
			throw new ServiceException('JMAP mailbox creation failed');
		}
		// create in local store, using the full path name
		$mailbox->setName($name);
		$mailbox = $this->mailboxMapper->insert($mailbox);

		return $mailbox;
	}

	#[\Override]
	public function renameMailbox(Account $account, Mailbox $mailbox, string $newName): Mailbox {
		if ($mailbox->getRemoteId() === null) {
			throw new ServiceException('JMAP mailbox is missing a remote id');
		}

		$this->jmapOperationsService->connect($account);

		// extract the mailbox name from the full path for remote operation
		$pathParts = explode(self::DELIMITER, $newName);
		if (count($pathParts) === 1) {
			$mailboxName = $newName;
		} else {
			$mailboxName = array_pop($pathParts);
		}
		// update remote store, using only the mailbox name
		$mailbox->setName($mailboxName);
		$mailbox = $this->jmapOperationsService->collectionModify($mailbox->getRemoteId(), $mailbox, ['name']);
		if ($mailbox === null) {
			throw new ServiceException('JMAP mailbox rename failed');
		}
		// update local store, with the full path name
		try {
			$mailbox->setName($newName);
			return $this->mailboxMapper->update($mailbox);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("The renamed mailbox $newName does not exist", 0, $e);
		}
	}

	#[\Override]
	public function deleteMailbox(Account $account, Mailbox $mailbox): void {
		if ($mailbox->getRemoteId() === null) {
			throw new ServiceException('JMAP mailbox is missing a remote id');
		}

		$this->jmapOperationsService->connect($account);

		// delete from remote store
		$result = $this->jmapOperationsService->collectionDestroy($mailbox->getRemoteId());
		if ($result === null) {
			throw new ServiceException('JMAP mailbox deletion failed');
		}
		// delete from local store
		$this->mailboxMapper->delete($mailbox);
	}

	#[\Override]
	public function subscribeMailbox(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		if ($mailbox->getRemoteId() === null) {
			throw new ServiceException('JMAP mailbox is missing a remote id');
		}

		$this->jmapOperationsService->connect($account);

		// update subscription attribute
		$attributes = json_decode($mailbox->getAttributes() ?? '[]', true);
		if (!is_array($attributes)) {
			$attributes = [];
		}
		if ($subscribed) {
			$attributes[] = '\\subscribed';
		} else {
			$attributes = array_filter($attributes, static function ($attribute) {
				return $attribute !== '\\subscribed';
			});
		}
		$mailbox->setAttributes(json_encode(array_values(array_unique($attributes))));
		// update remote store
		$mailbox = $this->jmapOperationsService->collectionModify($mailbox->getRemoteId(), $mailbox, ['subscribed']);
		if ($mailbox === null) {
			throw new ServiceException('JMAP mailbox subscription update failed');
		}
		// update local store
		try {
			return $this->mailboxMapper->update($mailbox);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('The updated mailbox does not exist', 0, $e);
		}
	}

	
	/**
	 * @param Mailbox[] $remoteMailboxes
	 * @return array<string, string>
	 */
	private function constructMailboxSyncNameLookup(array $remoteMailboxes): array {
		$mailboxesByRid = [];
		foreach ($remoteMailboxes as $remoteMailbox) {
			$rid = $remoteMailbox->getRemoteId();
			if ($rid === null) {
				continue;
			}

			$mailboxesByRid[$rid] = $remoteMailbox;
		}

		$lookup = [];
		$visiting = [];
		$resolveMailboxPath = function (string $rid) use (&$resolveMailboxPath, $mailboxesByRid, &$lookup, &$visiting): string {
			if (isset($lookup[$rid])) {
				return $lookup[$rid];
			}

			$mailbox = $mailboxesByRid[$rid];
			if (isset($visiting[$rid])) {
				$this->logger->warning('Detected cyclic JMAP mailbox parent relationship', [
					'rid' => $rid,
				]);

				return $mailbox->getName();
			}

			$visiting[$rid] = true;
			$path = $mailbox->getName();
			$parentRid = $mailbox->getRemoteParentId();

			if ($parentRid !== null) {
				if (isset($mailboxesByRid[$parentRid])) {
					$path = $resolveMailboxPath($parentRid) . self::DELIMITER . $path;
				} else {
					$this->logger->warning('JMAP mailbox parent missing from sync payload', [
						'rid' => $rid,
						'parentRid' => $parentRid,
					]);
				}
			}

			unset($visiting[$rid]);
			$lookup[$rid] = $path;

			return $path;
		};

		foreach (array_keys($mailboxesByRid) as $rid) {
			$resolveMailboxPath($rid);
		}

		return $lookup;
	}

	private function mergeMailbox(Mailbox $target, Mailbox $source, array $omit = []): Mailbox {
		if (!in_array('name', $omit, true)) {
			$target->setName($source->getName());
		}
		if (!in_array('nameHash', $omit, true)) {
			$target->setNameHash($source->getNameHash());
		}
		$target->setRemoteId($source->getRemoteId());
		$target->setAttributes($source->getAttributes());
		$target->setDelimiter($source->getDelimiter());
		$target->setMessages($source->getMessages());
		$target->setUnseen($source->getUnseen());
		$target->setSelectable($source->getSelectable() === true);
		$target->setSpecialUse($source->getSpecialUse());
		$target->setMyAcls($source->getMyAcls());
		$target->setShared($source->isShared() === true);

		return $target;
	}

}
