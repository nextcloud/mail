<?php
namespace OCA\Mail\Tests\Imap;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Mailbox;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
	private static $account;

	public static function setUpBeforeClass() {
		if (false === \getenv('EMAIL_USERNAME')) {
			throw new \RuntimeException(
				'Please set environment variable EMAIL_USERNAME before running functional tests'
			);
		}

		if (false === \getenv('EMAIL_PASSWORD')) {
			throw new \RuntimeException(
				'Please set environment variable EMAIL_PASSWORD before running functional tests'
			);
		}
		$user = \getenv('EMAIL_USERNAME');
		$password = \getenv('EMAIL_PASSWORD');
		$a = new MailAccount();
		$a->setId(-1);
		$a->setName('ownCloudMail');
		$a->setInboundHost('imap.gmail.com');
		$a->setInboundPort(993);
		$a->setInboundUser($user);
		$a->setInboundPassword($password);
		$a->setInboundSslMode('ssl');
		$a->setEmail($user);
		$a->setOutboundHost('smtp.gmail.com');
		$a->setOutboundPort(465);
		$a->setOutboundUser($user);
		$a->setOutboundPassword($password);
		$a->setOutboundSslMode('ssl');

		self::$account = new Account($a);
		self::$account->getImapConnection();
	}

	/**
	 * @param $name
	 * @return \OCA\Mail\Mailbox
	 */
	public function createMailBox($name) {
		$uniqueName = $name . uniqid();

		try {
			$mailbox = $this->getTestAccount()->getMailbox($uniqueName);
			$this->deleteMailbox($mailbox);
		} catch (\Exception $e) {
			// Ignore mailbox not found
		}

		return $this->getTestAccount()->createMailbox($uniqueName);
	}

	/**
	 * @return Account
	 */
	protected function getTestAccount() {
		return self::$account;
	}

	private function deleteMailbox($mailbox) {
		$this->getTestAccount()->deleteMailbox($mailbox);
	}

	protected function createTestMessage(
		Mailbox $mailbox,
		$subject = 'Don\'t panic!',
		$contents = 'Don\'t forget your towel',
		$from = 'someone@there.com',
		$to = 'me@here.com'
	) {
		$message = "From: $from
Subject: $subject
To: $to
Message-ID: <20150415133206.Horde.M8uzSs0lxFX6uUE2sc6_rw5@localhost>
User-Agent: Horde Application Framework 5
Date: Wed, 15 Apr 2015 13:32:06 +0000
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
MIME-Version: 1.0


$contents";
		$mailbox->saveMessage($message);
	}

}
