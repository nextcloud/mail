<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\System;

use Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Socket;
use Horde_Mail_Rfc822_Address;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCP\IServerContainer;
use OCP\IUser;
use OCP\IUserManager;
use function range;
use Throwable;

require_once __DIR__ . '/../../../../lib/base.php';
require_once __DIR__ . '/../../vendor/autoload.php';

function create_text_message(Horde_Imap_Client_Socket $imapClient,
							 string $sender,
							 string $receiver,
							 string $subject,
							 array $flags = [],
							 string $bodyText = '') {
	$headers = [
		'From' => new Horde_Mail_Rfc822_Address($sender),
		'To' => new Horde_Mail_Rfc822_Address($receiver),
		'Subject' => $subject,
	];

	$mail = new Horde_Mime_Mail();
	$mail->addHeaders($headers);
	$body = new Horde_Mime_Part();
	$body->setType('text/plain');
	$body->setContents($bodyText);
	$mail->setBasePart($body);

	$raw = $mail->getRaw();
	$data = stream_get_contents($raw);

	$imapClient->append('INBOX', [
		[
			'data' => $data,
			'flags' => $flags,
		]
	]);
}

function create_test_user($userManager, $testUID, $testPwd): IUser {
	$testUser = $userManager->createUser($testUID, $testPwd);

	if ($testUser === false) {
		throw new Exception("Could not create user $testUID");
	}

	return $testUser;
}

function create_mail_account(string $uid,
							 string $name,
							 string $email,
							 string $username,
							 AccountService $accountService): MailAccount {
	exec("docker exec -it ncimaptest /opt/bin/useradd $username mypasswd");

	$mailAccount = new MailAccount();
	$mailAccount->setUserId($uid);
	$mailAccount->setName($name);
	$mailAccount->setEmail($email);
	$mailAccount->setInboundHost('localhost');
	$mailAccount->setInboundPort(993);
	$mailAccount->setInboundSslMode('ssl');
	$mailAccount->setInboundUser($username);
	$mailAccount->setInboundPassword(OC::$server->getCrypto()->encrypt('mypasswd'));

	$mailAccount->setOutboundHost('localhost');
	$mailAccount->setOutboundPort(2525);
	$mailAccount->setOutboundUser($username);
	$mailAccount->setOutboundPassword(OC::$server->getCrypto()->encrypt('mypasswd'));
	$mailAccount->setOutboundSslMode('none');

	$account = $accountService->save($mailAccount);

	return $account;
}

const MAIL_BODY = <<<MAILBODY
Hi,

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.

At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
MAILBODY;

/**
 * @param $imapClientFactory
 * @param $account1
 * @param $email1
 */
function create_test_data(IMAPClientFactory $imapClientFactory,
						  Account $account1,
						  string $email1,
						  Account $account2,
						  string $email2) {
	echo "Creating test data... ";

	$imapClient1 = $imapClientFactory->getClient($account1);
	$imapClient2 = $imapClientFactory->getClient($account2);

	// First, let's create some unseen messages (not actually shown)
	$flags = [
		Horde_Imap_Client::FLAG_SEEN,
	];
	foreach (range(0, 50) as $i) {
		create_text_message($imapClient1, $email1, $email1, 'A message', $flags);
		create_text_message($imapClient2, $email2, $email2, 'A message', $flags);
	}
	foreach (get_test_data1() as list($sender, $subject)) {
		create_text_message($imapClient1, $sender, 'lauretta.lahman@protonmail.com', $subject, [], MAIL_BODY);
	}

	echo "done\n";
}

function get_test_data1(): array {
	return [
		['Leon Green <leon.green@acme.inc>', 'Outline for tomorrows meeting'],
		['Louis Johnson <louis.johnson@acme.inc>', 'New projector for room 1.02'],
		['Christine Scott <christine.scott@acme.inc>', 'Parental leave of Leon Green'],
		['Molly Allen <molly.allen@acme.inc>', 'Our homepage is down :('],
	];
}

try {

	/** @var IServerContainer $serverContainer */
	$serverContainer = OC::$server;
	/** @var IUserManager $userManager */
	$userManager = $serverContainer->query(IUserManager::class);
	/** @var AccountService $accountService */
	$accountService = $serverContainer->query(AccountService::class);
	/** @var IMAPClientFactory $imapClientFactory */
	$imapClientFactory = $serverContainer->query(IMAPClientFactory::class);

	$testUID = 'testuser' . rand(0, PHP_INT_MAX);
	$testPwd = $testUID . 'pwd';

	echo "Creating test user... ";
	create_test_user($userManager, $testUID, $testPwd);
	echo "done: $testUID\n";

	$email1 = $testUID . 1 . '@domain.tld';
	$email2 = $testUID . 2 . '@domain.tld';

	echo "Creating test mail accounts... ";
	// Random data from https://duckduckgo.com/?q=random+name&ia=answer
	$account1 = new Account(
		create_mail_account(
			$testUID,
			'Lauretta Lahman',
			'lauretta.lahman@protonmail.com',
			$email1,
			$accountService
		)
	);
	$account2 = new Account(
		create_mail_account(
			$testUID,
			'Lauretta Lahman',
			'l.lahman@gmail.com',
			$email2,
			$accountService
		)
	);
	echo "done\n";

	create_test_data($imapClientFactory, $account1, $email1, $account2, $email2);

	echo "Credentials: $testUID : $testPwd\n";
	echo "You may now log in at https://localhost/login?user=$testUID&redirect_url=/apps/mail\n";
} catch (Throwable $t) {
	echo "An error occurred: " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine();
	throw $t;
}

