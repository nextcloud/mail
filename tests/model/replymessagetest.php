<?php

namespace OCA\Mail\Tests\Model;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use OCA\Mail\Model\ReplyMessage;

class ReplyMessageTest extends MessageTest {

	protected function setUp() {
		parent::setUp();

		$this->message = new ReplyMessage();
	}

	public function testSubject() {
		$subject = 'test message';

		$this->message->setSubject($subject);

		// "Re: " should be added
		$this->assertSame("Re: $subject", $this->message->getSubject());
	}

	public function testSubjectReStacking() {
		$subject = 'Re: test message';

		$this->message->setSubject($subject);

		// Subject shouldn't change
		$this->assertSame($subject, $this->message->getSubject());
	}

	public function testSubjectReCaseStacking() {
		$subject = 'RE: test message';

		$this->message->setSubject($subject);

		// Subject shouldn't change
		$this->assertSame($subject, $this->message->getSubject());
	}

}
