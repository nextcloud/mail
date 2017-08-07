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

use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Tests\Integration\TestCase;

class MailTransmissionIntegrationTest extends TestCase {

	/** @var Account */
	private $account;

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
		$this->transmission = OC::$server->query(IMailTransmission::class);
	}

	public function testSendMail() {
		$message = NewMessageData::fromRequest($this->account, 'recipient@domain.com', null, null, 'greetings', 'hello there', []);
		$reply = new RepliedMessageData($this->account, null, null);
		$this->transmission->sendMessage('ferdinand', $message, $reply);
	}

}
