<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
		$a->setQuotaPercentage(10);
		$a->setTrashRetentionDays(60);
		$a->setOutOfOfficeFollowsSystem(true);

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
			'quotaPercentage' => 10,
			'trashRetentionDays' => 60,
			'junkMailboxId' => null,
			'snoozeMailboxId' => null,
			'searchBody' => false,
			'outOfOfficeFollowsSystem' => true,
			'debug' => false,
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
			'quotaPercentage' => null,
			'trashRetentionDays' => 60,
			'junkMailboxId' => null,
			'snoozeMailboxId' => null,
			'searchBody' => false,
			'outOfOfficeFollowsSystem' => false,
			'debug' => false,
		];
		$a = new MailAccount($expected);
		// TODO: fix inconsistency
		$expected['name'] = $expected['accountName'];
		unset($expected['accountName']);
		$this->assertEquals($expected, $a->toJson());
	}
}
