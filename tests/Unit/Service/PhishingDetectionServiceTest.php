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

namespace OCA\Mail\Tests\Integration\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Headers;
use OCA\Mail\PhishingDetectionResult;

use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCA\Mail\Service\PhishingDetection\PhishingDetectionService;
use OCA\Mail\Service\PhishingDetection\ReplyToCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class PhishingDetectionServiceTest extends TestCase {

	private ContactsIntegration|MockObject $contactsIntegration;
	private IL10N|MockObject $l10n;
	private ContactCheck|MockObject $contactCheck;
	private CustomEmailCheck|MockObject $customEmailCheck;
	private DateCheck|MockObject $dateCheck;
	private ReplyToCheck|MockObject $replyToCheck;
	private PhishingDetectionService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->contactCheck = $this->createMock(ContactCheck::class);
		$this->customEmailCheck = $this->createMock(customEmailCheck::class);
		$this->dateCheck = $this->createMock(DateCheck::class);
		$this->replyToCheck = $this->createMock(ReplyToCheck::class);
		$this->service = new PhishingDetectionService($this->contactCheck, $this->customEmailCheck, $this->dateCheck, $this->replyToCheck);
	}

	

	public function testCheckHeadersForPhishing() {
		$headerStream = fopen(__DIR__ . '/../../data/phishing-mail-headers.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$this->replyToCheck->expects($this->once())
			->method('run')
			->with('jhondoe@example.com', 'batman@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false));
		$this->contactCheck->expects($this->once())
			->method('run')
			->with('Jhon Doe', 'jhondoe@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false));
		$this->dateCheck->expects($this->once())
			->method('run')
			->with('Tue, 28 May 3024 13:02:15 +0200')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false));
		$this->customEmailCheck->expects($this->once())
			->method('run')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, false));
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, false);
		$this->assertFalse($result["warning"]);
	}

}
