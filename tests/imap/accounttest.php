<?php

namespace OCA\Mail\Tests\Imap;

class AccountTest extends AbstractTest {

	public function testListMailBoxes() {
		$newMailBox = $this->createMailBox('nasty stuff');
		$mailBoxes = $this->getTestAccount()->getListArray();
		$this->assertInternalType('array', $mailBoxes);

		$m = array_filter($mailBoxes['folders'], function($item) use ($newMailBox) {
			return $item['name'] === $newMailBox->getDisplayName();
		});
		$this->assertTrue(count($m) === 1);
	}

	public function testListMessages() {
		$newMailBox = parent::createMailBox('nasty stuff');
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(0, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(0, count($messages));
		$this->createTestMessage($newMailBox);
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(1, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(1, count($messages));
	}

	public function testGetChangedMailboxes() {
		$newMailBox = parent::createMailBox('nasty stuff');
		$status = $newMailBox->getStatus();
		$changedMailBoxes = $this->getTestAccount()->getChangedMailboxes([
			$newMailBox->getFolderId() => [ 'uidvalidity' => $status['uidvalidity'], 'uidnext' => $status['uidnext'] ]
		]);

		$this->assertEquals(0, count($changedMailBoxes));

		$this->createTestMessage($newMailBox);

		$changedMailBoxes = $this->getTestAccount()->getChangedMailboxes([
			$newMailBox->getFolderId() => [ 'uidvalidity' => $status['uidvalidity'], 'uidnext' => $status['uidnext'] ]
		]);

		$this->assertEquals(1, count($changedMailBoxes));
		$this->assertEquals(1, count($changedMailBoxes[$newMailBox->getFolderId()]['messages']));
	}

	public function testGetChangedMailboxesForNotExisting() {
		$changedMailBoxes = $this->getTestAccount()->getChangedMailboxes([
			'you-dont-know-me' => ['uidvalidity' => 0, 'uidnext' => 0]
		]);

		$this->assertEquals(0, count($changedMailBoxes));
	}

}
