<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use Test\TestCase;
use OCA\Mail\AppInfo\Application;

class ApplicationTest extends TestCase {

	public function testConstrucor() {
		// Not really a test – it's just about code coverage
		$app = new Application();
	}

}
