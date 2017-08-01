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

namespace OCA\Mail\Tests\Service;

use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCP\Security\ICrypto;
use PHPUnit_Framework_TestCase;

class MailTransmissionIntegrationTest extends PHPUnit_Framework_TestCase {

	/** @var ICrypto */
	private $crypo;
	
	/** @var IMailTransmission */
	private $transmission;

	protected function setUp() {
		parent::setUp();

		$this->crypo = OC::$server->getCrypto();
		$this->transmission = OC::$server->query(IMailTransmission::class);
	}

	public function testSendMail() {
		$account = new Account(MailAccount::fromParams([
			'email' => 'user@domain.tld',
			'inboundHost' => 'localhost',
			'inboundPort' => '993',
			'inboundSslMode' => 'none',
			'inboundUser' => 'user@domain.tld',
			'inboundPassword' => $this->crypo->encrypt('mypassword'),
			'outboundHost' => 'smtp.mailtrap.io',
			'outboundPort' => '25',
			'outboundSslMode' => 'none',
			'outboundUser' => 'xxx',
			'outboundPassword' => $this->crypo->encrypt('xxx'),
		]));
		$message = NewMessageData::fromRequest($account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$reply = new RepliedMessageData($account, null, null);
		$this->transmission->sendMessage($message, $reply);
	}

}
