<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestUser;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\IUser;

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

	protected function setUp() {
		parent::setUp();

		$crypo = OC::$server->getCrypto();
		$this->account = new Account(MailAccount::fromParams([
				'email' => 'user@domain.tld',
				'inboundHost' => 'localhost',
				'inboundPort' => '993',
				'inboundSslMode' => 'ssl',
				'inboundUser' => 'user@domain.tld',
				'inboundPassword' => $crypo->encrypt('mypassword'),
				'outboundHost' => 'localhost',
				'outboundPort' => '2525',
				'outboundSslMode' => 'none',
				'outboundUser' => 'user@domain.tld',
				'outboundPassword' => $crypo->encrypt('mypassword'),
		]));
		$this->attachmentService = OC::$server->query(IAttachmentService::class);
		$this->user = $this->createTestUser();
		$userFolder = OC::$server->getUserFolder($this->user->getUID());
		$this->transmission = new MailTransmission(OC::$server->query(AddressCollector::class), $userFolder, $this->attachmentService, OC::$server->query(SmtpClientFactory::class), OC::$server->query(Logger::class));
	}

	public function testSendMail() {
		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$reply = new RepliedMessageData($this->account, null, null);
		$this->transmission->sendMessage('ferdinand', $message, $reply);
	}

	public function testSendMailWithLocalAttachment() {
		$file = new UploadedFile([
			'name' => 'text.txt',
			'tmp_name' => dirname(__FILE__) . '/../../data/mail-message-123.txt',
		]);
		$this->attachmentService->addFile('gerald', $file);
		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', [
				[
					'isLocal' => 'true',
					'id' => 13,
				],
		]);
		$reply = new RepliedMessageData($this->account, null, null);
		$this->transmission->sendMessage('gerald', $message, $reply);
	}

	public function testSendMailWithCloudAttachment() {
		$userFolder = OC::$server->getUserFolder($this->user->getUID());
		$userFolder->newFile('text.txt');
		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', [
				[
					'isLocal' => false,
					'fileName' => 'text.txt',
				],
		]);
		$reply = new RepliedMessageData($this->account, null, null);
		$this->transmission->sendMessage($this->user->getUID(), $message, $reply);
	}

	public function testSendReply() {
		$inbox = base64_encode('inbox');
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('subject')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);

		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$reply = new RepliedMessageData($this->account, $inbox, $originalUID);
		$uid = $this->transmission->sendMessage('ferdinand', $message, $reply);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
		$this->assertMessageSubject('Sent', $uid, 'Re: greetings');
	}

	public function testSendReplyWithoutSubject() {
		$inbox = base64_encode('inbox');
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('subject')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);

		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, null, 'hello there', []);
		$reply = new RepliedMessageData($this->account, $inbox, $originalUID);
		$uid = $this->transmission->sendMessage('ferdinand', $message, $reply);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
		$this->assertMessageSubject('Sent', $uid, 'Re: reply test');
	}

	public function testSendReplyWithoutReplySubject() {
		$inbox = base64_encode('inbox');
		$mb = $this->getMessageBuilder();
		$originalMessage = $mb->from('from@domain.tld')
			->to('to@domain.tld')
			->subject('subject')
			->subject('reply test')
			->finish();
		$originalUID = $this->saveMessage('inbox', $originalMessage);

		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'Re: reply test', 'hello there', []);
		$reply = new RepliedMessageData($this->account, $inbox, $originalUID);
		$uid = $this->transmission->sendMessage('ferdinand', $message, $reply);

		$this->assertMailboxExists('Sent');
		$this->assertMessageCount(1, 'Sent');
		$this->assertMessageSubject('Sent', $uid, 'Re: reply test');
	}

	public function testSaveNewDraft() {
		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$uid = $this->transmission->saveDraft($message);
		// There should be a new mailbox …
		$this->assertMailboxExists('Drafts');
		// … and it should have exactly one message …
		$this->assertMessageCount(1, 'Drafts');
		// … and the correct content
		$this->assertMessageContent('Drafts', $uid, 'hello there');
	}

	public function testReplaceDraft() {
		$message1 = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello t', []);
		$uid = $this->transmission->saveDraft($message1);
		$message2 = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$this->transmission->saveDraft($message2, $uid);

		$this->assertMessageCount(1, 'Drafts');
	}

}
