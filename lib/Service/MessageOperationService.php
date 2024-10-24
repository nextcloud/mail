<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\MailManager;

class MessageOperationService {

    public function __construct(
        protected IMAPClientFactory $clientFactory,
        protected MailAccountMapper $accountMapper,
        protected MailboxMapper $mailboxMapper,
        protected MessageMapper $messageMapper,
        protected MailManager $mailManager,
        protected ImapMessageMapper $imapMessageMapper
    ) {}

    // group messages by mailbox ['mailbox_id' => [message_id, message_id]]
    protected function groupByMailbox(array $collection) {
        return array_reduce($collection, function ($carry, $pair) {
            if (!isset($carry[$pair['mailbox_id']])) {
                $carry[$pair['mailbox_id']] = [];
            }
            $carry[$pair['mailbox_id']][] = $pair['uid'];
            return $carry;
        }, []);
    }

    // group mailboxes by account ['account_id' => [mailbox object]]
    protected function groupByAccount(array $collection) {
        return array_reduce($collection, function ($carry, $entry) {
            if (!isset($carry[$entry->getAccountId()])) {
                $carry[$entry->getAccountId()] = [];
            }
            $carry[$entry->getAccountId()][] = $entry;
            return $carry;
        }, []);
    }

    /**
     * @param string $userId system user id
     * @param array<int,int> $identifiers message ids
	 * @param array<string,bool> $flags message flags
     */
    public function changeFlags(string $userId, array $identifiers, array $flags): void {

        // retrieve meta data [uid, mailbox_id] for all messages
        $messages = $this->groupByMailbox($this->messageMapper->findMailboxAndUid($identifiers));
        // retrieve all mailboxes
        $mailboxes = $this->groupByAccount($this->mailboxMapper->findByIds(array_keys($messages)));
        // retrieve all accounts
        $accounts = $this->accountMapper->findByIds(array_keys($mailboxes));

        foreach ($accounts as $account) {
            $account = new Account($account);
            // determine if account belongs to the user and skip if not
            if ($account->getUserId() != $userId) {
                continue;
            }
            
            $client = $this->clientFactory->getClient($account);

            try {
                foreach ($mailboxes[$account->getId()] as $mailbox) {
                    foreach ($flags as $flag => $value) {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        // Only send system flags to the IMAP server as other flags might not be supported
                        $imapFlags = $this->mailManager->filterFlags($client, $account, $flag, $mailbox->getName());
                        if (empty($imapFlags)) {
                            continue;
                        }
                        if ($value) {
                            $this->imapMessageMapper->addFlags($client, $mailbox, $messages[$mailbox->getId()], $imapFlags);
                        } else {
                            $this->imapMessageMapper->removeFlags($client, $mailbox, $messages[$mailbox->getId()], $imapFlags);
                        }
                    }
                }

            } catch (Horde_Imap_Client_Exception $e) {
                // TODO: Add proper error handling
            } finally {
                $client->logout();
            }
        }
        
    }

}