<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Tests\Unit\Model;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_DateTime;
use Horde_Mime_Part;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Tag;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Html;
use OCP\IURLGenerator;
use OCP\IRequest;

class IMAPMessageTest extends TestCase {
	/** @var Html|MockObject */
	private $htmlService;

	protected function setUp(): void {
		$this->htmlService = $this->createMock(Html::class);
	}

	public function testIconvHtmlMessage() {
		$urlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()
			->getMock();
		//linkToRoute 'mail.proxy.proxy'
		$urlGenerator->expects($this->any())
			->method('linkToRoute')
			->will($this->returnCallback(function ($url) {
				return "https://docs.example.com/server/go.php?to=$url";
			}));
		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();
		$htmlService = new Html($urlGenerator, $request);

		$part = Horde_Mime_Part::parseMessage(file_get_contents(__DIR__ . '/../../data/mail-message-123.txt'),
			['level' => 1]);
		$plainTextBody = $part[$part->findBody('plain')]->getContents();
		$htmlBody = $part[$part->findBody('html')]->getContents();

		$inlineAttachmentPart = $part->getPart(2);
		$message = new IMAPMessage(
			1234,
			'<1576747741.9.1432038946316.JavaMail.root@ip-172-32-11-10>',
			[],
			AddressList::parse('from@mail.com'),
			AddressList::parse('to@mail.com'),
			AddressList::parse('cc@mail.com'),
			AddressList::parse('bcc@mail.com'),
			AddressList::parse('reply-to@mail.com'),
			'core/master has new results',
			$plainTextBody,
			$htmlBody,
			true,
			[],
			[],
			false,
			[],
			new Horde_Imap_Client_DateTime('2016-01-01 00:00:00'),
			'',
			'disposition',
			'',
			false,
			false,
			false,
			$htmlService,
		);

		$actualHtmlBody = $message->getHtmlBody(123);
		$this->assertTrue(strlen($actualHtmlBody) > 1000);

		$actualPlainTextBody = $message->getPlainBody();
		$this->assertEquals($plainTextBody, $actualPlainTextBody);
	}

	public function testSerialize() {
		$data = new Horde_Imap_Client_Data_Fetch();
		$data->setUid(1234);
		$m = new IMAPMessage(
			1234,
			'foo',
			[ Horde_Imap_Client::FLAG_SEEN, Tag::LABEL_IMPORTANT ],
			AddressList::parse('from@mail.com'),
			AddressList::parse('to@mail.com'),
			AddressList::parse('cc@mail.com'),
			AddressList::parse('bcc@mail.com'),
			AddressList::parse('reply-to@mail.com'),
			'subject',
			'',
			'',
			true,
			[],
			[],
			false,
			[],
			new Horde_Imap_Client_DateTime('2016-01-01 00:00:00'),
			'',
			'disposition',
			'',
			false,
			false,
			false,
			$this->htmlService,
		);

		$json = $m->jsonSerialize();

		$this->assertEquals([
			'uid' => 1234,
			'messageId' => 'foo',
			'from' => [ [ 'label' => 'from@mail.com', 'email' => 'from@mail.com' ] ],
			'to' => [ [ 'label' => 'to@mail.com', 'email' => 'to@mail.com' ] ],
			'cc' => [ [ 'label' => 'cc@mail.com', 'email' => 'cc@mail.com' ] ],
			'bcc' => [ [ 'label' => 'bcc@mail.com', 'email' => 'bcc@mail.com' ] ],
			'subject' => 'subject',
			'dateInt' => 1451606400,
			'flags' => [
				'seen' => true,
				'flagged' => false,
				'answered' => false,
				'deleted' => false,
				'draft' => false,
				'forwarded' => false,
				'hasAttachments' => false,
				'mdnsent' => false,
				'important' => true,
			],
			'hasHtmlBody' => true,
			'dispositionNotificationTo' => 'disposition',
			'scheduling' => [],
		], $json);
		$this->assertEquals(1234, $json['uid']);
	}
}
