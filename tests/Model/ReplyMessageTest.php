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
namespace OCA\Mail\Tests\Model;

use OCA\Mail\Model\ReplyMessage;

class ReplyMessageTest extends MessageTest {

	protected function setUp() {
		parent::setUp();

		$this->message = new ReplyMessage();
	}

	public function testSubject() {
		$subject = 'test message';

		$this->message->setSubject($subject);

		$this->assertSame('Re: test message', $this->message->getSubject());
	}

	public function testSubjectReStacking() {
		$subject = 'Re: test message';

		$this->message->setSubject($subject);

		$this->assertSame($subject, $this->message->getSubject());
	}

	public function testSubjectReCaseStacking() {
		$subject = 'RE: test message';

		$this->message->setSubject($subject);

		$this->assertSame('Re: test message', $this->message->getSubject());
	}

	public function testSubjectChineseStacking() {
		$subject = '回复: test message';

		$this->message->setSubject($subject);

		$this->assertSame('Re: test message', $this->message->getSubject());
	}

	public function testSubjectTurkishCaseStacking() {
		$subject = 'İlt: test message';

		$this->message->setSubject($subject);

		$this->assertSame('Re: test message', $this->message->getSubject());
	}

	public function testSubjectGreekStacking() {
		$subject = 'ΣΧΕΤ: test message';

		$this->message->setSubject($subject);

		$this->assertSame('Re: test message', $this->message->getSubject());
	}
}
