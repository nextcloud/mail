<?php

/**
* ownCloud - Mail
*
* @author Thomas Müller
* @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\Mail\Tests\Service;

class HtmlTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider linkDetectionProvider
	 * @param $expected
	 * @param $text
	 */
	public function testLinkDetection($expected, $text){

		$html = new \OCA\Mail\Service\Html();
		$withLinks = $html->convertLinks($text);
		$this->assertSame($expected, $withLinks);
    }

	public function linkDetectionProvider() {
		return array(
			array('abc', 'abc'),
			array('<a href="http://google.com" target="_blank" rel="noreferrer">http://google.com</a>', 'http://google.com'),
			array('<a href="https://google.com" target="_blank" rel="noreferrer">https://google.com</a>', 'https://google.com'),
			array('<a href="ftp://google.com" target="_blank" rel="noreferrer">ftp://google.com</a>', 'ftp://google.com'),
			array('<a href="http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/" target="_blank" rel="noreferrer">http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/</a>', 'http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/'),
			array('<a href="https://travis-ci.org/owncloud/music/builds/22037091" target="_blank" rel="noreferrer">https://travis-ci.org/owncloud/music/builds/22037091</a>', 'https://travis-ci.org/owncloud/music/builds/22037091'),
			array('(<a href="ftp://google.com" target="_blank" rel="noreferrer">ftp://google.com</a>)', '(ftp://google.com)'),
			array('<a href="https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1" target="_blank" rel="noreferrer">https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1</a>', 'https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1'),
			array('(<a href="https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1" target="_blank" rel="noreferrer">https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1</a>)', '(https://build.opensuse.org/package/view_file/isv:ownCloud:community:7.0/owncloud/debian.changelog?expand=1)'),
			array('<a href="http://apps.owncloud.com/content/show.php/Music?content=160485" target="_blank" rel="noreferrer">http://apps.owncloud.com/content/show.php/Music?content=160485</a>', 'http://apps.owncloud.com/content/show.php/Music?content=160485'),
			array('<a href="https://groups.google.com/forum/#!forum/ctpug" target="_blank" rel="noreferrer">https://groups.google.com/forum/#!forum/ctpug</a>', 'https://groups.google.com/forum/#!forum/ctpug'),
			array('<a href="http://www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Daps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser" target="_blank" rel="noreferrer">http://www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Daps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser</a>', 'http://www.amazon.de/s/ref=nb_sb_noss?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&url=search-alias%3Daps&field-keywords=Fax%2C+Kopierer+scanner+Laser&rh=i%3Aaps%2Ck%3AFax%5Cc+Kopierer+scanner+Laser'),
		);
	}

	/**
	 * @dataProvider parseMailBodyProvider
	 * @param $expected
	 * @param $text
	 */
	public function testParseMailBody($expectedBody, $expectedSignature, $text){

		$html = new \OCA\Mail\Service\Html();
		list($b, $s) = $html->parseMailBody($text);
		$this->assertSame($expectedBody, $b);
		$this->assertSame($expectedSignature, $s);
	}

	public function parseMailBodyProvider() {
		return array(
			array('abc', null, 'abc'),
			array('abc', 'def', "abc-- \r\ndef"),
			array("abc-- \r\ndef", 'ghi', "abc-- \r\ndef-- \r\nghi"),
		);
	}
}
