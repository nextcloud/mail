<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestUser;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Send\Chain;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Server;
use Psr\Log\NullLogger;

class MailTransmissionIntegrationTest extends TestCase {
	use ImapTest,
		TestUser;

	/** @var Account */
	private $account;

	/** @var IUser */
	private $user;

	/** @var IAttachmentService */
	private $attachmentService;

	private Chain $chain;

	private LocalMessageMapper $localMessageMapper;
	private LocalMessage $message;

	protected function setUp(): void {
		parent::setUp();

		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->user = $this->createTestUser();

		/** @var ICrypto $crypo */
		$crypo = \OCP\Server::get(\OCP\Security\ICrypto::class);
		/** @var MailAccountMapper $mapper */
		$mapper = Server::get(MailAccountMapper::class);
		$mailAccount = MailAccount::fromParams([
			'userId' => $this->user->getUID(),
			'name' => 'Test User',
			'email' => 'user@domain.tld',
			'inboundHost' => '127.0.0.1',
			'inboundPort' => '993',
			'inboundSslMode' => 'ssl',
			'inboundUser' => 'user@domain.tld',
			'inboundPassword' => $crypo->encrypt('mypassword'),
			'outboundHost' => '127.0.0.1',
			'outboundPort' => '25',
			'outboundSslMode' => 'none',
			'outboundUser' => 'user@domain.tld',
			'outboundPassword' => $crypo->encrypt('mypassword'),
		]);
		$mapper->insert($mailAccount);

		$this->account = new Account($mailAccount);
		$this->attachmentService = Server::get(IAttachmentService::class);
		$userFolder = OC::$server->getUserFolder($this->user->getUID());

		$recipientMapper = Server::get(RecipientMapper::class);
		$recipient = new Recipient();
		$recipient->setType(Recipient::TYPE_TO);
		$recipient->setEmail('recipient@domain.com');
		$recipientMapper->insert($recipient);

		$this->localMessageMapper = Server::get(LocalMessageMapper::class);
		$this->message = new LocalMessage();
		$this->message->setAccountId($this->account->getId());
		$this->message->setSubject('greetings');
		$this->message->setBodyHtml('hello there');
		$this->message->setType(LocalMessage::TYPE_OUTGOING);
		$this->message->setHtml(false);
		$this->message->setRecipients([$recipient]);
		$this->message->setStatus(LocalMessage::STATUS_RAW);
		$this->localMessageMapper->insert($this->message);
		// Make sure the mailbox preferences are set
		/** @var MailboxSync $mbSync */
		$mbSync = Server::get(MailboxSync::class);
		$mbSync->sync($this->account, new NullLogger(), true);

		$this->chain = Server::get(Chain::class);

	}

	public function testSendMail() {
		$this->chain->process($this->account, $this->message);

		$this->addToAssertionCount(1);
	}

	public function testSendMailWithLocalAttachment() {
		$file = new UploadedFile([
			'name' => 'text.txt',
			'tmp_name' => __DIR__ . '/../../data/mail-message-123.txt',
		]);

		$localAttachment = $this->attachmentService->addFile($this->user->getUID(), $file);

		$this->message->setAttachments([$localAttachment]);

		$this->chain->process($this->account, $this->message);

		$this->addToAssertionCount(1);
	}

	public function testSendReply() {
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);
		/** @var MailboxSync $mbSync */
		$mbSync = Server::get(MailboxSync::class);
		$mbSync->sync($this->account, new NullLogger(), true);
		/** @var MailboxMapper $mailboxMapper */
		$mailboxMapper = Server::get(MailboxMapper::class);
		$inbox = $mailboxMapper->find($this->account, 'INBOX');
		$messageInReply = new Message();
		$messageInReply->setUid($originalUID);
		$messageInReply->setMessageId('message@server');
		$messageInReply->setMailboxId($inbox->getId());
		$this->message->setInReplyToMessageId($messageInReply->getInReplyTo());

		$this->chain->process($this->account, $this->message);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
	}

	public function testSendReplyWithoutSubject() {
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);
		/** @var MailboxSync $mbSync */
		$mbSync = Server::get(MailboxSync::class);
		$mbSync->sync($this->account, new NullLogger(), true);
		/** @var MailboxMapper $mailboxMapper */
		$mailboxMapper = Server::get(MailboxMapper::class);
		$inbox = $mailboxMapper->find($this->account, 'INBOX');
		$messageInReply = new Message();
		$messageInReply->setUid($originalUID);
		$messageInReply->setMessageId('message@server');
		$messageInReply->setMailboxId($inbox->getId());
		$this->message->setSubject('');
		$this->message->setInReplyToMessageId($messageInReply->getInReplyTo());

		$this->chain->process($this->account, $this->message);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
	}

	public function testSendReplyWithoutReplySubject() {
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);
		/** @var MailboxSync $mbSync */
		$mbSync = Server::get(MailboxSync::class);
		$mbSync->sync($this->account, new NullLogger(), true);
		/** @var MailboxMapper $mailboxMapper */
		$mailboxMapper = Server::get(MailboxMapper::class);
		$inbox = $mailboxMapper->find($this->account, 'INBOX');
		$messageInReply = new Message();
		$messageInReply->setUid($originalUID);
		$messageInReply->setMessageId('message@server');
		$messageInReply->setMailboxId($inbox->getId());
		$this->message->setSubject('Re: reply test');
		$this->message->setInReplyToMessageId($messageInReply->getInReplyTo());

		$this->chain->process($this->account, $this->message);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
	}
}
