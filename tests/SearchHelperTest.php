<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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
 *
 */

namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\SearchHelper;

class SearchHelperTest extends TestCase {
	private function search($filter) {
		$helper = new SearchHelper();
		$query = $helper->parseFilterString($filter);
		return (string)($query->build()['query']);
	}

	public function testSearchEmpty() {
		$this->assertEquals('ALL', $this->search(''));
	}

	public function testSearchTest() {
		$this->assertEquals('TEXT "dummy text"', $this->search('dummy text'));
	}

	public function testSearchUnread() {
		$this->assertEquals('UNSEEN', $this->search('is:unread'));
	}

	public function testSearchNotAnswered() {
		$this->assertEquals('UNANSWERED', $this->search('not:answered'));
	}

	public function testSearchFrom() {
		$this->assertEquals('FROM somebody@example.com', $this->search('from:somebody@example.com'));
	}

	public function testSearchMixed() {
		$this->assertEquals('UNSEEN FROM somebody@example.com TEXT nextcloud', $this->search('from:somebody@example.com is:unread nextcloud'));
	}
}
