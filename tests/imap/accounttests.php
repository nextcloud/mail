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


}