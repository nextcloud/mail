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

}

