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

namespace OCA\Mail\Service;

use Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Db\MailAccount;

class AutoConfig {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var string
	 */
	private $userId;

	public function __construct($mapper, $userId) {
		$this->mapper = $mapper;
		$this->userId = $userId;
	}

	/**
	 * try to log into the mail account using different ports
	 * and use SSL if available
	 * IMAP - port 143
	 * Secure IMAP (IMAP4-SSL) - port 585
	 * IMAP4 over SSL (IMAPS) - port 993
	 */
	private function testAccount($email, $host, $users, $password, $name) {
		if (!is_array($users)) {
			$users = array($users);
		}

		$ports = array(143, 585, 993);
		$encryptionProtocols = array('ssl', 'tls', null);
		$hostPrefixes = array('', 'imap.');
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			foreach ($ports as $port) {
				foreach ($encryptionProtocols as $encryptionProtocol) {
					foreach($users as $user) {
						try {
							$this->getImapConnection($url, $port, $user, $password, $encryptionProtocol);
							$this->log("Test-Account-Successful: $this->userId, $url, $port, $user, $encryptionProtocol");
							return $this->addAccount($this->userId, $email, $url, $port, $user, $password, $encryptionProtocol, $name);
						} catch (\Horde_Imap_Client_Exception $e) {
							$error = $e->getMessage();
							$this->log("Test-Account-Failed: $this->userId, $url, $port, $user, $encryptionProtocol -> $error");
						}
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param string $user
	 * @param string $password
	 * @param string $ssl_mode
	 * @return \Horde_Imap_Client_Socket a ready to use IMAP connection
	 */
	private function getImapConnection($host, $port, $user, $password, $ssl_mode)
	{
		$imapConnection = new Horde_Imap_Client_Socket(array(
			'username' => $user, 'password' => $password, 'hostspec' => $host, 'port' => $port, 'secure' => $ssl_mode, 'timeout' => 2));
		$imapConnection->login();
		return $imapConnection;
	}

	/**
	 * Saves the mail account credentials for a users mail account
	 *
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @param string $ocUserId
	 * @param $email
	 * @param $inboundHost
	 * @param $inboundHostPort
	 * @param $inboundUser
	 * @param $inboundPassword
	 * @param string|null $inboundSslMode
	 * @return int MailAccountId
	 */
	private function addAccount($ocUserId, $email, $inboundHost, $inboundHostPort, $inboundUser, $inboundPassword, $inboundSslMode, $name)
	{

		$mailAccount = new MailAccount();
		$mailAccount->setOcUserId($ocUserId);
		$mailAccount->setMailAccountId(time());
		$mailAccount->setMailAccountName($name);
		$mailAccount->setEmail($email);
		$mailAccount->setInboundHost($inboundHost);
		$mailAccount->setInboundHostPort($inboundHostPort);
		$mailAccount->setInboundSslMode($inboundSslMode);
		$mailAccount->setInboundUser($inboundUser);
		$mailAccount->setInboundPassword($inboundPassword);

		$this->mapper->save($mailAccount);

		return $mailAccount->getMailAccountId();
	}

	/**
	 * @param $email
	 * @param $ocUserId
	 * @param $password
	 * @return int|null
	 */
	public function createAutoDetected($email, $password, $name)
	{
		// splitting the email address into user and host part
		list($user, $host) = explode("@", $email);

		$ispdb = $this->queryMozillaIspDb($host, true);
		if (!empty($ispdb)) {
			if (isset($ispdb['imap'])) {
				foreach($ispdb['imap'] as $imap) {
					$host = $imap['hostname'];
					$port = $imap['port'];
					$encryptionProtocol = null;
					if ($imap['socketType'] === 'SSL') {
						$encryptionProtocol = 'ssl';
					}
					if ($imap['socketType'] === 'STARTTLS') {
						$encryptionProtocol = 'tls';
					}
					if ($imap['username'] === '%EMAILADDRESS%' ) {
						$user = $email;
					} elseif ($imap['username'] === '%EMAILLOCALPART%') {
						list($user,) = explode("@", $email);
					} else {
						$this->log("Unknown username variable: " . $imap['username']);
						return null;
					}
					try {
						$this->getImapConnection($host, $port, $user, $password, $encryptionProtocol);
						$this->log("Test-Account-Successful: $this->userId, $host, $port, $user, $encryptionProtocol");
						return $this->addAccount($this->userId, $email, $host, $port, $user, $password, $encryptionProtocol, $name);
					} catch (\Horde_Imap_Client_Exception $e) {
						$error = $e->getMessage();
						$this->log("Test-Account-Failed: $this->userId, $host, $port, $user, $encryptionProtocol -> $error");
					}
				}
			}
			return null;
		}

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->getMxRecord($host);
		if ($mxHosts) {
			foreach($mxHosts as $mxHost) {
				$result = $this->testAccount($email, $mxHost, array($user, $email), $password, $name);
				if ($result) {
					return $result;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->testAccount($email, $host, array($user, $email), $password, $name);
	}

	private function log($message) {
		// TODO: DI
		\OC::$server->getLogger()->info($message, array('app' => 'mail'));
	}

	/**
	 * @param $host
	 * @return bool|array
	 */
	private function getMxRecord($host) {
		if (getmxrr($host, $mx_records, $mx_weight) == false) {
			return false;
		}

		// TODO: sort by weight
		return $mx_records;
	}

	protected function queryMozillaIspDb($domain, $tryMx=true)
	{
		if (strpos($domain, '@') !== false) {
			list(,$domain) = explode('@', $domain);
		}

		$url = 'https://autoconfig.thunderbird.net/v1.1/'.$domain;
		try {
			$xml = @simplexml_load_file($url);
			if (!$xml->emailProvider) {
				return array();
			}
			$provider = array(
				'displayName' => (string)$xml->emailProvider->displayName,
			);
			foreach($xml->emailProvider->children() as $tag => $server) {
				if (!in_array($tag, array('incomingServer', 'outgoingServer'))) {
					continue;
				}
				foreach($server->attributes() as $name => $value) {
					if ($name == 'type') {
						$type = (string)$value;
					}
				}
				$data = array();
				foreach($server as $name => $value) {
					foreach($value->children() as $tag => $val) {
						$data[$name][$tag] = (string)$val;
					}
					if (!isset($data[$name])) {
						$data[$name] = (string)$value;
					}
				}
				$provider[$type][] = $data;
			}
		}
		catch(Exception $e) {
			// ignore own not-found exception or xml parsing exceptions
			unset($e);

			if ($tryMx && ($dns = dns_get_record($domain, DNS_MX))) {
				$domain = $dns[0]['target'];
				if (!($provider = $this->queryMozillaIspDb($domain, false))) {
					list(,$domain) = explode('.', $domain, 2);
					$provider = $this->queryMozillaIspDb($domain, false);
				}
			} else {
				$provider = array();
			}
		}
		return $provider;
	}

}
