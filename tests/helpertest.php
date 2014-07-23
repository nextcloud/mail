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

class TestHelper extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider linkDetectionProvider
	 * @param $expected
	 * @param $text
	 */
	public function testLinkDetection($expected, $text){

		$withLinks = \OCA\Mail\Message::convertLinks($text);
		$this->assertSame($expected, $withLinks);
    }

	public function linkDetectionProvider() {
		return array(
			array('abc', 'abc'),
			array('<a href="http://google.com" target="_blank">http://google.com</a>', 'http://google.com'),
			array('<a href="https://google.com" target="_blank">https://google.com</a>', 'https://google.com'),
			array('<a href="ftp://google.com" target="_blank">ftp://google.com</a>', 'ftp://google.com'),
			array('<a href="http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/" target="_blank">http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/</a>', 'http://www.themukt.com/2014/07/23/take-control-cloud-owncloud-7/'),
			array('<a href="https://travis-ci.org/owncloud/music/builds/22037091" target="_blank">https://travis-ci.org/owncloud/music/builds/22037091</a>', 'https://travis-ci.org/owncloud/music/builds/22037091'),
		);
	}
}
