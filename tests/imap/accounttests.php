<?php

namespace OCA\Mail\Tests\Imap;

class AccountTests extends AbstractTest {

	public function testListMailBoxes() {
		$mailBoxes = $this->getTestAccount()->getListArray();
		$this->assertInternalType('array', $mailBoxes);
	}
}