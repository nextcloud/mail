<?php
namespace OCA\Mail\Tests\Imap;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;

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
	 * @return Account
	 */
	protected function getTestAccount() {
		return self::$account;
	}

}
