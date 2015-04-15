<?php

namespace OCA\Mail\Tests\Imap;

class AccountTests extends AbstractTest {

	public function testListMailBoxes() {
		$newMailBox = parent::createMailBox('nasty stuff');
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
		$messages = $count = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(0, count($messages));
		$this->createTestMessage($newMailBox);
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(1, $count);
		$messages = $count = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(1, count($messages));
	}

}