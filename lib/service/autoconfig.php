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
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCP\Security\ICrypto;

class AutoConfig {

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $userId;

	/**
	 * @var ICrypto
	 */
	private $crypto;

	/**
	 * @param Logger $logger
	 * @param string $userId
	 * @param ICrypto $crypto
	 */
	public function __construct(Logger $logger, $userId, ICrypto $crypto) {
		$this->logger = $logger;
		$this->userId = $userId;
		$this->crypto = $crypto;
	}

	/**
	 * @param $email
	 * @param $host
	 * @param $users
	 * @param $password
	 * @param $name
	 * @return null|MailAccount
	 */
	private function testImap($email, $host, $users, $password, $name) {
		if (!is_array($users)) {
			$users = [$users];
		}

		$ports = [143, 585, 993];
		$encryptionProtocols = ['ssl', 'tls', null];
		$hostPrefixes = ['', 'imap.'];
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			if (gethostbyname($url) === $url) {
				continue;
			}
			foreach ($ports as $port) {
				if (!$this->canConnect($url, $port)) {
					continue;
				}
				foreach ($encryptionProtocols as $encryptionProtocol) {
					foreach($users as $user) {
						try {
							return $this->connectImap($email, $password, $name, $host, $port, $encryptionProtocol, $user);
						} catch (\Horde_Imap_Client_Exception $e) {
							$error = $e->getMessage();
							$this->logger->info("Test-Account-Failed: $this->userId, $url, $port, $user, $encryptionProtocol -> $error");
						}
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param MailAccount $account
	 * @param $host
	 * @param $users
	 * @param $password
	 * @param bool $withHostPrefix
	 * @return null|MailAccount
	 */
	private function testSmtp(MailAccount $account,
							  $host,
							  $users,
							  $password,
							  $withHostPrefix = false) {
		if (!is_array($users)) {
			$users = [$users];
		}

		// port 25 should be the last one to test
		$ports = [587, 465, 25];
		$protocols = ['ssl', 'tls', null];
		$hostPrefixes = [''];
		if ($withHostPrefix) {
			$hostPrefixes = ['', 'imap.'];
		}
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			if (gethostbyname($url) === $url) {
				continue;
			}
			foreach ($ports as $port) {
				if (!$this->canConnect($url, $port)) {
					continue;
				}
				foreach ($protocols as $protocol) {
					foreach($users as $user) {
						try {
							$account->setOutboundHost($url);
							$account->setOutboundPort($port);
							$account->setOutboundUser($user);
							$password = $this->crypto->encrypt($password);
							$account->setOutboundPassword($password);
							$account->setOutboundSslMode($protocol);

							$a = new Account($account);
							$smtp = $a->createTransport();
							$smtp->getSMTPObject();

							$this->logger->info("Test-Account-Successful: $this->userId, $url, $port, $user, $protocol");

							return $account;
						} catch (\Exception $e) {
							$error = $e->getMessage();
							$this->logger->info("Test-Account-Failed: $this->userId, $url, $port, $user, $protocol -> $error");
						}
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	public function createAutoDetected($email, $password, $name) {

		// splitting the email address into user and host part
		list(, $host) = explode("@", $email);

		$ispdb = $this->queryMozillaIspDb($host, true);
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
						list($user,) = explode("@", $email);
					} else {
						$this->logger->info("Unknown username variable: " . $imap['username']);
						return null;
					}
					try {
						$account = $this->connectImap($email, $password, $name, $host, $port, $encryptionProtocol, $user);
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
							list($user,) = explode("@", $email);
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
						$smtp->getSMTPObject();

						break;
					} catch(\PEAR_Exception $ex) {
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
	 * @param $host
	 * @return bool|array
	 */
	private function getMxRecord($host) {
		if (getmxrr($host, $mx_records, $mx_weight) === false) {
			return false;
		}

		// TODO: sort by weight
		return $mx_records;
	}

	/**
	 * @param string $domain
	 * @param bool $tryMx
	 * @return array
	 */
	protected function queryMozillaIspDb($domain, $tryMx=true) {
		if (strpos($domain, '@') !== false) {
			list(,$domain) = explode('@', $domain);
		}

		$url = 'https://autoconfig.thunderbird.net/v1.1/'.$domain;
		try {
			$xml = @simplexml_load_file($url);
			if (!is_object($xml) || !$xml->emailProvider) {
				return [];
			}
			$provider = [
				'displayName' => (string)$xml->emailProvider->displayName,
			];
			foreach($xml->emailProvider->children() as $tag => $server) {
				if (!in_array($tag, ['incomingServer', 'outgoingServer'])) {
					continue;
				}
				foreach($server->attributes() as $name => $value) {
					if ($name == 'type') {
						$type = (string)$value;
					}
				}
				$data = [];
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
				$provider = [];
			}
		}
		return $provider;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	private function detectImapAndSmtp($email, $password, $name) {
		$account = $this->detectImap($email, $password, $name);
		if (is_null($account)) {
			return null;
		}

		$this->detectSmtp($account, $email, $password);

		return $account;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	private function detectImap($email, $password, $name) {

		// splitting the email address into user and host part
		list($user, $host) = explode("@", $email);

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->getMxRecord($host);
		if ($mxHosts) {
			foreach ($mxHosts as $mxHost) {
				$result = $this->testImap($email, $mxHost, [$user, $email], $password, $name);
				if ($result) {
					return $result;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->testImap($email, $host, [$user, $email], $password, $name);
	}

	/**
	 * @param $account
	 * @param $email
	 * @param $password
	 * @return MailAccount|null
	 */
	private function detectSmtp(MailAccount $account, $email, $password) {

		// splitting the email address into user and host part
		list($user, $host) = explode("@", $email);

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->getMxRecord($host);
		if ($mxHosts) {
			foreach ($mxHosts as $mxHost) {
				$result = $this->testSmtp($account, $mxHost, [$user, $email], $password);
				if ($result) {
					return $result;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->testSmtp($account, $host, [$user, $email], $password, true);
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @param $host
	 * @param $port
	 * @param string|null $encryptionProtocol
	 * @param $user
	 * @return MailAccount
	 */
	private function connectImap($email, $password, $name, $host, $port, $encryptionProtocol, $user) {
		$account = new MailAccount();
		$account->setUserId($this->userId);
		$account->setName($name);
		$account->setEmail($email);
		$account->setInboundHost($host);
		$account->setInboundPort($port);
		$account->setInboundSslMode($encryptionProtocol);
		$account->setInboundUser($user);
		$password = $this->crypto->encrypt($password);
		$account->setInboundPassword($password);

		$a = new Account($account);
		$a->getImapConnection();
		$this->logger->info("Test-Account-Successful: $this->userId, $host, $port, $user, $encryptionProtocol");
		return $account;
	}

	/**
	 * @param string $url
	 * @param integer $port
	 * @return bool
	 */
	private function canConnect($url, $port) {
		$fp = fsockopen ( $url, $port);
		if (is_resource($fp)) {
			fclose($fp);
			return true;
		}
		return false;
	}

}
