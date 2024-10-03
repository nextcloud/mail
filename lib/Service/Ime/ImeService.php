<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Ime;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IAppConfig;

class ImeService  {
	
	public function __construct(
		private IAppConfig $config,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
	) {}

	public function getEnabled(): bool {
		return $this->config->getValueBool(Application::APP_ID, 'ime_enabled', false);
	}

	public function setEnabled(bool $value): void {
		$this->config->setValueBool(Application::APP_ID, 'ime_enabled', $value);
	}

	public function getRestrictions(): string {
		return $this->config->getValueString(Application::APP_ID, 'ime_restrict_ip', '');
	}

	public function setRestrictions(string $value): void {
		$this->config->setValueString(Application::APP_ID, 'ime_restrict_ip', $value);
	}

	public function handle(array $events): void {
		
		foreach ($events as $event) {
			
			// determine if basic required informaiton is present
			if (!isset($event['type'])) {
				continue;
			}
			if (!isset($event['user'])) {
				continue;
			}
			// determine if user account exists
			$accounts = $this->accountService->findByInboundUserId($event['user']);
			if (count($accounts) === 0) {
				continue;
			}

			$result = match ($event['type']) {
				'MailboxCreate' => $this->handleMailboxCreate($accounts, $event),
				'MailboxDelete' => $this->handleMailboxDelete($accounts, $event),
				'MailboxRename' => $this->handleMailboxRename($accounts, $event),
				'MessageAppend' => $this->handleMessageAppend($accounts, $event),
				'MessageExpunge' => $this->handleMessageExpunge($accounts, $event),
				default => "Unknown event type"
			};
		}

	}

	public function handleMailboxCreate($accounts, array $event): void {
		
		// determine if basic required informaiton is present
		if (!isset($event['folder'])) {
			return;
		}
		// 
		foreach ($accounts as $account) {

			// retieve mailbox with the new name
			// this should error as this mailbox should not exist yet
			// if mailbox name already exists ignore the event as two mailboxes with the same name are not permitted. 
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder']);
			} catch (DoesNotExistException $e) {
			}
			if (isset($mailbox)) {
				continue;
			}

			$mailbox = new Mailbox();
			$mailbox->setAccountId($account->getId());
			$mailbox->setName($event['folder']);
			$mailbox->setNameHash(md5($event['folder']));
			$mailbox->setSelectable(true);
			$mailbox->setDelimiter('/');
			$mailbox->setMessages(is_numeric($event['messages']) ? (int)$event['messages'] : 0);
			$mailbox->setUnseen(is_numeric($event['unseen']) ? (int)$event['unseen'] : 0);
			$mailbox = $this->mailboxMapper->insert($mailbox);

		}

	}

	public function handleMailboxDelete($accounts, array $event): void {
		
		// determine if basic required informaiton is present
		if (!isset($event['folder'])) {
			return;
		}
		// 
		foreach ($accounts as $account) {

			// retieve mailbox with the name
			// if the mailbox does not exist ignore the event as this might be a lagging or duplicate event
			// or the folder hierarchy has been updated in another way
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder']);
			} catch (DoesNotExistException $e) {
				continue;
			}
			
			$this->messageMapper->deleteAll($mailbox);
			$this->mailboxMapper->delete($mailbox);

		}


	}

	public function handleMailboxRename($accounts, array $event): void {
		
		// determine if basic required informaiton is present
		if (!isset($event['folder_from'])) {
			return;
		}
		if (!isset($event['folder_to'])) {
			return;
		}
		// 
		foreach ($accounts as $account) {

			// retieve mailbox with the new name
			// this should error as this mailbox should not exist yet
			// if mailbox name already exists ignore the event as two mailboxes with the same name are not permitted. 
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder_to']);
			} catch (DoesNotExistException $e) {
			}
			if (isset($mailbox)) {
				continue;
			}
			// retieve mailbox with the current name
			// if the mailbox does not exist ignore the event as this might be a lagging or duplicate event
			// or the folder hierarchy has been updated in another way
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder_from']);
			} catch (DoesNotExistException $e) {
				continue;
			}
			// if we got this far its safe to update the folder name
			$mailbox->setName($event['folder_to']);
			$mailbox->setNameHash(md5($event['folder_to']));
			if (is_numeric($event['messages'])) { $mailbox->setMessages((int)$event['messages']); }
			if (is_numeric($event['unseen'])) { $mailbox->setUnseen((int)$event['unseen']); }
			$this->mailboxMapper->update($mailbox);
			
		}

	}

	public function handleMessageAppend($accounts, array $event): void {
		
		// determine if basic required informaiton is present
		if (!isset($event['folder_id'])) {
			return;
		}
		if (!is_numeric($event['uid'])) {
			return;
		}
		// 
		foreach ($accounts as $account) {

			// retieve mailbox
			// if the mailbox does not exist ignore the event as this might be a lagging event
			// or the folder hierarchy has been updated in another way
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder_id']);
			} catch (DoesNotExistException $e) {
				continue;
			}
			// update mailbox
			if (is_numeric($event['messages'])) { $mailbox->setMessages((int)$event['messages']); }
			if (is_numeric($event['unseen'])) { $mailbox->setUnseen((int)$event['unseen']); }
			$this->mailboxMapper->update($mailbox);
			// create message
			$message = new Message();
			$message->setMailboxId($mailbox->getId());
			$message->setUid((int)$event['uid']);
			$message->setSentAt(is_numeric($event['date']) ? (int)$event['date'] : 0);
			$message->setSubject($event['subject']);
			$message->setPreviewText($event['snippet']);
			$this->messageMapper->insert($message);

		}

	}

	public function handleMessageExpunge($accounts, array $event): void {
		
		// determine if basic required informaiton is present
		if (!isset($event['folder_id'])) {
			return;
		}
		if (!is_numeric($event['uid'])) {
			return;
		}
		// 
		foreach ($accounts as $account) {

			// retieve mailbox
			// if the mailbox does not exist ignore the event as this might be a lagging event
			// or the folder hierarchy has been updated in another way
			try {
				$mailbox = $this->mailboxMapper->find($account, $event['folder_id']);
			} catch (DoesNotExistException $e) {
				continue;
			}
			// update mailbox
			if (is_numeric($event['messages'])) { $mailbox->setMessages((int)$event['messages']); }
			if (is_numeric($event['unseen'])) { $mailbox->setUnseen((int)$event['unseen']); }
			$this->mailboxMapper->update($mailbox);
			// delete message
			$this->messageMapper->deleteByUid($mailbox, (int)$event['uid']);		

		}

	}
}
