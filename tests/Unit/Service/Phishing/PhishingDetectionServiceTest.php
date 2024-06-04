<?php
/**
 * @copyright 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author  2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\ContactsIntegration;

use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCA\Mail\Service\PhishingDetection\ReplyToCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class PhishingDetectionServiceTest extends TestCase {

	private ContactsIntegration|MockObject $contactsIntegration;
	private IL10N|MockObject $l10n;
	private ContactCheck $contactCheck;
	private CustomEmailCheck $customEmailCheck;
	private DateCheck $dateCheck;
	private ReplyToCheck $replyToCheck;

	protected function setUp(): void {
		parent::setUp();
		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->contactCheck = new ContactCheck($this->contactsIntegration, $this->l10n);
		$this->customEmailCheck = new CustomEmailCheck($this->l10n);
		$this->dateCheck = new DateCheck($this->l10n);
		$this->replyToCheck = new ReplyToCheck($this->l10n);
	}

	public function testContactCheck() {
		$this->contactsIntegration->expects($this->once())
		->method('getContactsWithName')
		->willReturn([["id" => 1, "fn" => "John Doe", "email" => ["jhon@example.org","Doe@example.org"]]]);
		$result = $this->contactCheck->run("John Doe", "jhon.doe@example.org");
		$this->assertTrue($result->isPhishing());
	}

	public function testCustomEmailCheck() {
		$result = $this->customEmailCheck->run("jhon@example.org", "jhon.doe@example.org");
		$this->assertTrue($result->isPhishing());
	}

	public function testDateCheck() {
		$result = $this->dateCheck->run("2024-12-12 12:12:12");
		$this->assertTrue($result->isPhishing());
	}

	public function testReplyToCheck() {
		$result = $this->replyToCheck->run("jhon@example.org", "jhon.doe@example.org");
		$this->assertTrue($result->isPhishing());
	}

}
