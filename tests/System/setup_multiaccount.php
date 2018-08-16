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
use Horde_Imap_Client_Socket;
use Horde_Mail_Rfc822_Address;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use OC;
use OCA\Mail\Account;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SetupService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\IServerContainer;
use OCP\IUserManager;
use function str_random;

require_once __DIR__ . '/../../../../lib/base.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/TestMailAccount.php';

/** @var IServerContainer $serverContainer */
$serverContainer = OC::$server;

/** @var IUserManager $userManager */
$userManager = $serverContainer->query(IUserManager::class);

$testUID = 'testuser' . rand(0, PHP_INT_MAX);
$testPwd = $testUID . 'pwd';

$email1 = $testUID . 1 . '@domain.tld';
$email2 = $testUID . 2 . '@domain.tld';

$testUser = $userManager->createUser($testUID, $testPwd);

echo "Created user $testUID\n";

if ($testUser === false) {
	throw new Exception("Could not create user $testUID");
}

/** @var AccountService $accountService */
$accountService = $serverContainer->query(AccountService::class);

$testAccount1 = new TestMailAccount($email1);
$testAccount2 = new TestMailAccount($email2);

echo "Creating test mail accounts\n";
echo "Test mail accounts created\n";
$account1 = new Account($testAccount1->create($testUID, $accountService));
$account2 = new Account($testAccount2->create($testUID, $accountService));

echo "Creating test data\n";
/** @var IMAPClientFactory $imapClientFactory */
$imapClientFactory = $serverContainer->query(IMAPClientFactory::class);
$imapClient1 = $imapClientFactory->getClient($account1);
$imapClient2 = $imapClientFactory->getClient($account2);

function create_text_message(Horde_Imap_Client_Socket $imapClient, string $email, string $subject) {
	$headers = [
		//'From' => new Horde_Mail_Rfc822_Address('sender@domain.tld'),
		'To' => new Horde_Mail_Rfc822_Address($email),
		'Subject' => $subject,
	];

	$mail = new Horde_Mime_Mail();
	$mail->addHeaders($headers);
	$body = new Horde_Mime_Part();
	$body->setType('text/plain');
	$body->setContents('');
	$mail->setBasePart($body);

	$raw = $mail->getRaw();
	$data = stream_get_contents($raw);

	$imapClient->append('INBOX', [
		[
			'data' => $data,
		]
	]);
}

create_text_message($imapClient1, $email1, 'Message 1');

echo "Credentials: $testUID : $testPwd\n";
echo "You may now log in at https://localhost/login?user=$testUID&redirect_url=/apps/mail\n";
