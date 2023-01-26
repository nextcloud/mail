<?php

declare(strict_types=1);

/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;

class MailAccountTest extends TestCase {
	public function testToAPI() {
		$a = new MailAccount();
		$a->setId(3);
		$a->setName('Peter Parker');
		$a->setInboundHost('mail.marvel.com');
		$a->setInboundPort(159);
		$a->setInboundUser('spiderman');
		$a->setInboundPassword('xxxxxxxx');
		$a->setInboundSslMode('tls');
		$a->setEmail('peter.parker@marvel.com');
		$a->setId(12345);
		$a->setOutboundHost('smtp.marvel.com');
		$a->setOutboundPort(458);
		$a->setOutboundUser('spiderman');
		$a->setOutboundPassword('xxxx');
		$a->setOutboundSslMode('ssl');
		$a->setEditorMode('html');
		$a->setProvisioningId(null);
		$a->setOrder(13);

		$this->assertEquals([
			'id' => 12345,
			'accountId' => 12345,
			'name' => 'Peter Parker',
			'emailAddress' => 'peter.parker@marvel.com',
			'imapHost' => 'mail.marvel.com',
			'imapPort' => 159,
			'imapUser' => 'spiderman',
			'imapSslMode' => 'tls',
			'smtpHost' => 'smtp.marvel.com',
			'smtpPort' => 458,
			'smtpUser' => 'spiderman',
			'smtpSslMode' => 'ssl',
			'signature' => null,
			'editorMode' => 'html',
			'provisioningId' => null,
			'order' => 13,
			'showSubscribedOnly' => null,
			'personalNamespace' => null,
			'draftsMailboxId' => null,
			'sentMailboxId' => null,
			'trashMailboxId' => null,
			'archiveMailboxId' => null,
			'sieveEnabled' => false,
			'signatureAboveQuote' => false,
			'signatureMode' => null,
			'smimeCertificateId' => null,
		], $a->toJson());
	}

	public function testMailAccountConstruct() {
		$expected = [
			'id' => 12345,
			'accountId' => 12345,
			'accountName' => 'Peter Parker',
			'emailAddress' => 'peter.parker@marvel.com',
			'imapHost' => 'mail.marvel.com',
			'imapPort' => 159,
			'imapUser' => 'spiderman',
			'imapSslMode' => 'tls',
			'smtpHost' => 'smtp.marvel.com',
			'smtpPort' => 458,
			'smtpUser' => 'spiderman',
			'smtpSslMode' => 'ssl',
			'signature' => null,
			'editorMode' => null,
			'provisioningId' => null,
			'order' => null,
			'showSubscribedOnly' => false,
			'personalNamespace' => null,
			'draftsMailboxId' => null,
			'sentMailboxId' => null,
			'trashMailboxId' => null,
			'archiveMailboxId' => null,
			'sieveEnabled' => false,
			'signatureAboveQuote' => false,
			'signatureMode' => null,
			'smimeCertificateId' => null,
		];
		$a = new MailAccount($expected);
		// TODO: fix inconsistency
		$expected['name'] = $expected['accountName'];
		unset($expected['accountName']);
		$this->assertEquals($expected, $a->toJson());
	}
}
