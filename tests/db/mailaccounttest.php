<?php
/**
 * ownCloud - Mail
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;


class TestMailAccount extends \PHPUnit_Framework_TestCase {

	public function testToAPI() {
		$a = new MailAccount();
		$a->setId(3);
		$a->setName('Peter Parker');
		$a->setInboundHost('mail.marvel.com');
		$a->setInboundPort(159);
		$a->setInboundUser('spiderman');
		$a->setInboundPassword('xxxxxxxx');
		$a->setInboundSslMode('tls');
		$a->setEmail('peter.parker@marvel.com');
		$a->setId(12345);
		$a->setOutboundHost('smtp.marvel.com');
		$a->setOutboundPort(458);
		$a->setOutboundUser('spiderman');
		$a->setOutboundPassword('xxxx');
		$a->setOutboundSslMode('ssl');

		$this->assertEquals(array(
			'accountId' => 12345,
			'name' => 'Peter Parker',
			'emailAddress' => 'peter.parker@marvel.com',
			'imapHost' => 'mail.marvel.com',
			'imapPort' => 159,
			'imapUser' => 'spiderman',
			'imapSslMode' => 'tls',
			'smtpHost' => 'smtp.marvel.com',
			'smtpPort' => 458,
			'smtpUser' => 'spiderman',
			'smtpSslMode' => 'ssl'
		), $a->toJson());
	}

}
