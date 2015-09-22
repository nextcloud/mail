<?php

namespace OCA\Mail\Service\AutoConfig;

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use Horde_Mail_Transport_Smtphorde;
use OCP\Security\ICrypto;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;

class AutoConfig {

	/** @var Logger */
	private $logger;

	/** @var string */
	private $userId;

	/** @var ICrypto */
	private $crypto;

	/** @var MozillaIspDb */
	private $mozillaIspDb;

	/** @var MxRecord */
	private $mxRecord;

	/** @var ImapConnectivityTester */
	private $imapConnectivityTester;

	/** @var ImapServerDetector */
	private $imapServerDetector;

	/** @var ImapConnector */
	private $imapConnector;

	/** @var SmtpConnectivityTester */
	private $smtpConnectivityTester;

	/** @var SmtpServerDetector */
	private $smtpServerDetector;

	/**
	 * 
	 * @param Logger $logger
	 * @param string $userId
	 * @param \OCA\Mail\Service\AutoConfig\MozillaIspDb $mozillaIspDb
	 * @param \OCA\Mail\Service\AutoConfig\MxRecord $mxRecord
	 * @param \OCA\Mail\Service\AutoConfig\ImapConnectivityTester $imapTester
	 * @param \OCA\Mail\Service\AutoConfig\ImapServerDetector $imapDetector
	 * @param \OCA\Mail\Service\AutoConfig\SmtpConnectivityTester $smtpTester
	 * @param \OCA\Mail\Service\AutoConfig\SmtpServerDetector $smtpDetector
	 * @param \OCA\Mail\Service\AutoConfig\ImapConnector $imapConnector
	 * @param ICrypto $crypto
	 */
	public function __construct(Logger $logger, $userId,
		MozillaIspDb $mozillaIspDb, MxRecord $mxRecord,
		ImapConnectivityTester $imapTester, ImapServerDetector $imapDetector,
		SmtpConnectivityTester $smtpTester, SmtpServerDetector $smtpDetector,
		ImapConnector $imapConnector, ICrypto $crypto) {
		$this->logger = $logger;
		$this->userId = $userId;
		$this->crypto = $crypto;
		$this->mozillaIspDb = $mozillaIspDb;
		$this->mxRecord = $mxRecord;
		$this->imapConnectivityTester = $imapTester;
		$this->imapServerDetector = $imapDetector;
		$this->imapConnector = $imapConnector;
		$this->smtpConnectivityTester = $smtpTester;
		$this->smtpServerDetector = $smtpDetector;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	public function createAutoDetected($email, $password, $name) {

		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list(, $host) = explode("@", $email);

		$ispdb = $this->mozillaIspDb->query($host);
		if (!empty($ispdb)) {
			$account = null;
			if (isset($ispdb['imap'])) {
				foreach ($ispdb['imap'] as $imap) {
					$host = $imap['hostname'];
					$port = $imap['port'];
					$encryptionProtocol = null;
					if ($imap['socketType'] === 'SSL') {
						$encryptionProtocol = 'ssl';
					}
					if ($imap['socketType'] === 'STARTTLS') {
						$encryptionProtocol = 'tls';
					}
					if ($imap['username'] === '%EMAILADDRESS%') {
						$user = $email;
					} elseif ($imap['username'] === '%EMAILLOCALPART%') {
						list($user, ) = explode("@", $email);
					} else {
						$this->logger->info("Unknown username variable: " . $imap['username']);
						return null;
					}
					try {
						$account = $this->imapConnector->connect($email, $password, $name, $host,
							$port, $encryptionProtocol, $user);
						break;
					} catch (\Horde_Imap_Client_Exception $e) {
						$error = $e->getMessage();
						$this->logger->info("Test-Account-Failed: $this->userId, $host, $port, $user, $encryptionProtocol -> $error");
					}
				}
			}
			if (!is_null($account)) {
				foreach ($ispdb['smtp'] as $smtp) {
					try {
						if ($smtp['username'] === '%EMAILADDRESS%') {
							$user = $email;
						} elseif ($smtp['username'] === '%EMAILLOCALPART%') {
							list($user, ) = explode("@", $email);
						} else {
							$this->logger->info("Unknown username variable: " . $smtp['username']);
							return null;
						}

						$account->setOutboundHost($smtp['hostname']);
						$account->setOutboundPort($smtp['port']);
						$password = $this->crypto->encrypt($password);
						$account->setOutboundPassword($password);
						$account->setOutboundUser($user);
						$account->setOutboundSslMode(strtolower($smtp['socketType']));

						$a = new Account($account);
						$smtp = $a->createTransport();
						if ($smtp instanceof Horde_Mail_Transport_Smtphorde) {
							$smtp->getSMTPObject();
						}

						break;
					} catch (\PEAR_Exception $ex) {
						$error = $ex->getMessage();
						$this->logger->info("Test-Account-Failed(smtp): $error");
					}
				}
				return $account;
			}
		}

		$account = $this->detectImapAndSmtp($email, $password, $name);
		if (!is_null($account)) {
			return $account;
		}

		return null;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	private function detectImapAndSmtp($email, $password, $name) {
		$account = $this->imapServerDetector->detect($email, $password, $name);
		if (is_null($account)) {
			return null;
		}

		$this->smtpServerDetector->detect($account, $email, $password);

		return $account;
	}

}
