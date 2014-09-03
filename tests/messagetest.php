<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class MessageTest extends \PHPUnit_Framework_TestCase {

	public function testNoFrom() {
		$data = new Horde_Imap_Client_Data_Fetch();
		$m = new \OCA\Mail\Message(null, 'INBOX', 123, $data);

		$this->assertNull($m->getFrom());
		$this->assertNull($m->getFromEmail());
	}

	public function testGetReplyCcList() {
		$data = new Horde_Imap_Client_Data_Fetch();
		$data->setEnvelope(array(
			'to' => 'a@b.org, tom@example.org, b@example.org',
			'cc' => 'a@b.org, tom@example.org, a@example.org'
		));
		$m = new \OCA\Mail\Message(null, 'INBOX', 123, $data);

		$cc = $m->getReplyCcList('a@b.org');
		$this->assertTrue(is_array($cc));
		$this->assertEquals(3, count($cc));
		$cc = array_map(function($item) {
			return $item['email'];
		}, $cc);
		$this->assertTrue(in_array('tom@example.org', $cc));
		$this->assertTrue(in_array('a@example.org', $cc));
		$this->assertTrue(in_array('b@example.org', $cc));
	}
}

