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
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\Chain;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use OCA\Mail\Send\SentMailboxHandler;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\TransmissionService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Server;
use Psr\Log\LoggerInterface;
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

	/** @var IMailTransmission */
	private $transmission;
	private Chain $chain;

	private LocalMessageMapper $localMessageMapper;
	private LocalMessage $message;

	protected function setUp(): void {
		parent::setUp();

		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->user = $this->createTestUser();

		/** @var ICrypto $crypo */
		$crypo = OC::$server->getCrypto();
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

		$this->chain = new Chain(
			Server::get(SentMailboxHandler::class),
			Server::get(AntiAbuseHandler::class),
			Server::get(SendHandler::class),
			Server::get(CopySentMessageHandler::class),
			Server::get(FlagRepliedMessageHandler::class),
			$this->attachmentService,
			$this->localMessageMapper,
			Server::get(IMAPClientFactory::class),
		);

		$this->transmission = new MailTransmission(Server::get(IMAPClientFactory::class),
			Server::get(SmtpClientFactory::class),
			Server::get(IEventDispatcher::class),
			Server::get(MailboxMapper::class),
			Server::get(MessageMapper::class),
			Server::get(LoggerInterface::class),
			Server::get(PerformanceLogger::class),
			Server::get(AliasesService::class),
			Server::get(TransmissionService::class),
		);
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

	public function testSaveNewDraft() {
		$message = NewMessageData::fromRequest($this->account, 'greetings', 'hello there', 'recipient@domain.com', null, null, [], false);
		[,,$uid] = $this->transmission->saveDraft($message);
		// There should be a new mailbox …
		$this->assertMailboxExists('Drafts');
		// … and it should have exactly one message …
		$this->assertMessageCount(1, 'Drafts');
		// … and the correct content
		$this->assertMessageContent('Drafts', $uid, 'hello there');
	}

	public function testReplaceDraft() {
		$message1 = NewMessageData::fromRequest($this->account, 'greetings', 'hello t', 'recipient@domain.com', null, null, []);
		[,,$uid] = $this->transmission->saveDraft($message1);
		$message2 = NewMessageData::fromRequest($this->account, 'greetings', 'hello there', 'recipient@domain.com', null, null, []);
		$previous = new Message();
		$previous->setUid($uid);
		$this->transmission->saveDraft($message2, $previous);

		$this->assertMessageCount(1, 'Drafts');
	}
}
