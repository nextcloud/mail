<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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

namespace OCA\Mail\Tests\Service;

use OCA\Mail\Service\Html;

class HtmlTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider linkDetectionProvider
	 * @param $expected
	 * @param $text
	 */
	public function testLinkDetection($expected, $text){
		$urlGenerator = \OC::$server->getURLGenerator();
		$request = \OC::$server->getRequest();
		$html = new Html($urlGenerator, $request);
		$withLinks = $html->convertLinks($text);
		$this->assertSame($expected, $withLinks);
    }

	public function linkDetectionProvider() {
		return [
			['abc', 'abc'],
			['&lt;&gt;', '&lt;&gt;'],
			['<a href="http://google.com" rel="noreferrer" target="_blank">google.com</a>', 'http://google.com'],
			['<a href="https://google.com" rel="noreferrer" target="_blank">google.com</a>', 'https://google.com'],
			['<a href="ftp://google.com" rel="noreferrer" target="_blank">google.com</a>', 'ftp://google.com'],
			['<a href="http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/" rel="noreferrer" target="_blank">www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/</a>', 'http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/'],
			['<a href="https://travis-ci.org/owncloud/music/builds/22037091" rel="noreferrer" target="_blank">travis-ci.org/owncloud/music/builds/22037091</a>', 'https://travis-ci.org/owncloud/music/builds/22037091'],
			['(<a href="ftp://google.com" rel="noreferrer" target="_blank">google.com</a>)', '(ftp://google.com)'],
			['<a href="https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1" rel="noreferrer" target="_blank">build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog</a>', 'https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1'],
			['(<a href="https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1" rel="noreferrer" target="_blank">build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog</a>)', '(https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1)'],
			['<a href="http://apps.owncloud.com/content/show.php/Music?content=160485" rel="noreferrer" target="_blank">apps.owncloud.com/content/show.php/Music</a>', 'http://apps.owncloud.com/content/show.php/Music?content=160485'],
			['<a href="https://groups.google.com/forum/#!forum/ctpug" rel="noreferrer" target="_blank">groups.google.com/forum/</a>', 'https://groups.google.com/forum/#!forum/ctpug'],
//			array('<a href="http://www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Da/ps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser">www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Daps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser</a>', 'http://www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Daps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser'),
			['<a href="https://ci.owncloud.org/job/ownCloud-Documentation(7.0)/504/changes" rel="noreferrer" target="_blank">ci.owncloud.org/job/ownCloud-Documentation(7.0)/504/changes</a>', 'https://ci.owncloud.org/job/ownCloud-Documentation(7.0)/504/changes'],
			['<a href="https://communities.coverity.com/community/scan-(open-source)/content" rel="noreferrer" target="_blank">communities.coverity.com/community/scan-(open-source)/content</a>', 'https://communities.coverity.com/community/scan-(open-source)/content'],
			['<a href="https://ma.ellak.gr/events/5%CE%BF%CF%82-%CE%B5%CE%BA%CF%80%CE%B1%CE%B9%CE%B4%CE%B5%CF%85%CF%84%CE%B9%CE%BA%CF%8C%CF%82-%CE%BA%CF%8D%CE%BA%CE%BB%CE%BF%CF%82-%CF%83%CE%B5%CE%BC%CE%B9%CE%BD%CE%B1%CF%81%CE%AF%CF%89%CE%BD-%CE%B5-5/" rel="noreferrer" target="_blank">ma.ellak.gr/events/5ος-εκπαιδευτικός-κύκλος-σεμιναρίων-ε-5/</a>', 'https://ma.ellak.gr/events/5ος-εκπαιδευτικός-κύκλος-σεμιναρίων-ε-5/'],
		];
	}

	/**
	 * @dataProvider parseMailBodyProvider
	 * @param $expected
	 * @param $text
	 */
	public function testParseMailBody($expectedBody, $expectedSignature, $text){
		$urlGenerator = \OC::$server->getURLGenerator();
		$request = \OC::$server->getRequest();
		$html = new Html($urlGenerator, $request);
		list($b, $s) = $html->parseMailBody($text);
		$this->assertSame($expectedBody, $b);
		$this->assertSame($expectedSignature, $s);
	}

	public function parseMailBodyProvider() {
		return [
			['abc', null, 'abc'],
			['abc', 'def', "abc-- \r\ndef"],
			["abc-- \r\ndef", 'ghi', "abc-- \r\ndef-- \r\nghi"],
		];
	}
}
