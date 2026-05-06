<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use Exception;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\TransmissionService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class JmapTransmissionConnector implements ITransmissionConnector {

	public function __construct(
		private JmapOperationsService $jmapOperationsService,
		private TransmissionService $transmissionService,
		private AliasesService $aliasesService,
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function sendMessage(Account $account, LocalMessage $message, Mailbox $sentMailbox): void {
		$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);

		$name = $account->getName();
		$emailAddress = $account->getEMailAddress();

		if ($message->getAliasId() !== null) {
			try {
				$alias = $this->aliasesService->find($message->getAliasId(), $account->getUserId());
				$name = ($alias->getName() ?? $name);
				$emailAddress = $alias->getAlias();
			} catch (DoesNotExistException) {
				$this->logger->debug('The assigned alias no longer exists. Falling back to the default name and email address.', [
					'aliasId' => $message->getAliasId(),
					'accountId' => $account->getId(),
				]);
			}
		}

		$from = Address::fromRaw($name, $emailAddress);

		$sentMailboxRid = $sentMailbox->getRemoteId();
		if ($sentMailboxRid === null) {
			$this->logger->error('Sent mailbox does not have a JMAP remote ID', ['mailboxId' => $sentMailbox->getId()]);
			$message->setStatus(LocalMessage::STATUS_ERROR);
			return;
		}

		$this->jmapOperationsService->connect($account);

		$draftsMailboxRid = $this->resolveDraftsMailboxRid($account);
		if ($draftsMailboxRid === null) {
			$this->logger->error('No Drafts mailbox configured for JMAP send staging', ['accountId' => $account->getId()]);
			$message->setStatus(LocalMessage::STATUS_ERROR);
			return;
		}

		try {
			$identityId = $this->resolveIdentityId($emailAddress);
		} catch (Exception $e) {
			$this->logger->error('Could not resolve JMAP identity for send: ' . $e->getMessage(), ['exception' => $e]);
			$message->setStatus(LocalMessage::STATUS_ERROR);
			return;
		}

		$rcptTo = $this->collectEnvelopeRecipients($to, $cc, $bcc);
		$emailParams = $this->buildEmailParams($from, $to, $cc, $bcc, $message);

		try {
			$this->jmapOperationsService->entitySend(
				$identityId,
				$emailParams,
				$draftsMailboxRid,
				$sentMailboxRid,
				$from->getEmail() ?? $emailAddress,
				$rcptTo,
			);
			$message->setStatus(LocalMessage::STATUS_PROCESSED);
		} catch (Exception $e) {
			$status = $this->classifyJmapError($e->getMessage());
			$this->logger->error('JMAP send failed: ' . $e->getMessage(), ['exception' => $e]);
			$message->setStatus($status);
		}
	}

	#[\Override]
	public function saveMessage(Account $account, Mailbox $mailbox, LocalMessage $message, array $flags = []): void {
		$remoteId = $mailbox->getRemoteId();
		if ($remoteId === null) {
			throw new ServiceException("Mailbox {$mailbox->getId()} does not have a JMAP remote ID");
		}

		$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);
		$from = Address::fromRaw($account->getName(), $account->getEMailAddress());

		$emailParams = $this->buildEmailParams($from, $to, $cc, $bcc, $message);

		// Apply mailbox location and keyword flags
		$keywords = [];
		foreach ($flags as $flag) {
			$keywords[$flag] = true;
		}
		$emailParams->in($remoteId);
		if ($keywords !== []) {
			$emailParams->keywords($keywords);
		}

		$this->jmapOperationsService->connect($account);
		try {
			$this->jmapOperationsService->entitySave($emailParams);
		} catch (Exception $e) {
			throw new ServiceException('Could not save message to JMAP mailbox: ' . $e->getMessage(), 0, $e);
		}
	}

	#[\Override]
	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void {
		throw new ServiceException('MDN is not supported for JMAP accounts');
	}

	/**
	 * Build a MailParametersRequest from a LocalMessage.
	 *
	 * Uses the JMAP client parameter builders so the generated payload stays aligned
	 * with the request classes used elsewhere in the integration.
	 */
	private function buildEmailParams(
		Address $from,
		AddressList $to,
		AddressList $cc,
		AddressList $bcc,
		LocalMessage $message,
	): MailParametersRequest {
		$emailParams = new MailParametersRequest();
		$emailParams->from($from->getEmail() ?? '', $from->getLabel() ?? '');

		foreach ($to->iterate() as $address) {
			$emailParams->to($address->getEmail() ?? '', $address->getLabel() ?? '');
		}

		foreach ($cc->iterate() as $address) {
			$emailParams->cc($address->getEmail() ?? '', $address->getLabel() ?? '');
		}

		foreach ($bcc->iterate() as $address) {
			$emailParams->bcc($address->getEmail() ?? '', $address->getLabel() ?? '');
		}

		$emailParams->subject($message->getSubject() ?? '');

		if (($inReplyTo = $message->getInReplyToMessageId()) !== null) {
			$emailParams->inReplyTo($inReplyTo);
			$emailParams->references($inReplyTo);
		}

		$bodyPlain = $message->getBodyPlain() ?? '';
		$bodyHtml = $message->getBodyHtml();

		if ($bodyHtml !== null && $bodyHtml !== '') {
			$bodyStructure = $emailParams->bodyPartStructure();
			$bodyStructure->type('multipart/alternative');
			$bodyStructure->addPart()->id('text')->type('text/plain');
			$bodyStructure->addPart()->id('html')->type('text/html');

			$emailParams->bodyPartValue('text', $bodyPlain);
			$emailParams->bodyPartValue('html', $bodyHtml);
		} else {
			$emailParams->bodyTextPlain($bodyPlain, 'text');
		}

		return $emailParams;
	}

	/**
	 * Collect bare email addresses for the SMTP envelope from To, Cc, Bcc lists.
	 *
	 * @return string[]
	 */
	private function collectEnvelopeRecipients(AddressList $to, AddressList $cc, AddressList $bcc): array {
		$recipients = [];
		foreach ([$to, $cc, $bcc] as $list) {
			foreach ($list->iterate() as $address) {
				$email = $address->getEmail();
				if ($email !== null) {
					$recipients[] = $email;
				}
			}
		}
		return array_unique($recipients);
	}

	/**
	 * Find the JMAP identity ID whose email address matches the given address.
	 *
	 * Falls back to the first available identity if no exact match.
	 *
	 * @throws Exception when no identities are found on the server
	 */
	private function resolveIdentityId(string $emailAddress): string {
		$identities = $this->jmapOperationsService->identityFetch();
		if ($identities === []) {
			throw new Exception('No JMAP identities found on server');
		}
		foreach ($identities as $identity) {
			if (strtolower($identity->address() ?? '') === strtolower($emailAddress)) {
				return $identity->id();
			}
		}
		// fall back to first identity
		return $identities[0]->id();
	}

	/**
	 * Get the JMAP remote ID of the configured Drafts mailbox.
	 */
	private function resolveDraftsMailboxRid(Account $account): ?string {
		$draftsMailboxId = $account->getMailAccount()->getDraftsMailboxId();
		if ($draftsMailboxId === null) {
			return null;
		}
		try {
			$mailbox = $this->mailboxMapper->findById($draftsMailboxId);
			return $mailbox->getRemoteId();
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * Classify a JMAP server error string into a LocalMessage status constant.
	 *
	 * @return LocalMessage::STATUS_*
	 */
	private function classifyJmapError(string $errorMessage): int {
		$lower = strtolower($errorMessage);
		if (str_contains($lower, 'toomanyrecipients')) {
			return LocalMessage::STATUS_TOO_MANY_RECIPIENTS;
		}
		if (str_contains($lower, 'forbiddenfrom') || str_contains($lower, 'notpermitted')) {
			return LocalMessage::STATUS_ERROR;
		}
		// Network / HTTP errors and other transient failures are retriable
		return LocalMessage::STATUS_SMPT_SEND_FAIL;
	}
}
