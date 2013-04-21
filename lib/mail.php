<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace {
	// add include path to this apps 3rdparty
	$incPath = __DIR__."/../3rdparty";
	set_include_path(get_include_path() . PATH_SEPARATOR . $incPath);

	// load Horde's auto loader
	require_once 'Horde/Autoloader/Default.php';

	// bypass Horde Translation system
	Horde_Translation::setHandler('Horde_Imap_Client', new OC_Translation_Handler());
}

namespace OCA\Mail {

	class App
	{
		/**
		 * Extracts all matching contacts with email address and name
		 *
		 * @param $term
		 * @return array
		 */
		public static function getMatchingRecipient($term) {
			if (!\OCP\Contacts::isEnabled()) {
				return array();
			}

			$result = \OCP\Contacts::search($term, array('FN', 'EMAIL'));
			$receivers = array();
			foreach ($result as $r) {
				$id = $r['id'];
				$fn = $r['FN'];
				$email = $r['EMAIL'];
				if (!is_array($email)) {
					$email = array($email);
				}

				// loop through all email addresses of this contact
				foreach ($email as $e) {
					$displayName = "\"$fn\" <$e>";
					$receivers[] = array('id'    => $id,
										 'label' => $displayName,
										 'value' => $displayName);
				}
			}

			return $receivers;
		}

		/**
		 * Loads all user's accounts, connects to each server and queries all folders
		 *
		 * @static
		 * @param $user_id
		 * @return array
		 */
		public static function getFolders($user_id) {
			$response = array();

			// get all account configured by the user
			$accounts = App::getAccounts($user_id);

			// iterate ...
			foreach ($accounts as $account) {
				try {
					$response[] = $account->getListArray();
				} catch (\Horde_Imap_Client_Exception $e) {
					$response[] = array('id' => $account->getId(), 'email' => $account->getEMailAddress(), 'error' => $e->getMessage());
				}
			}

			return $response;
		}

		/**
		 * @static
		 * @param $user_id
		 * @param $account_id
		 * @param $folder_id
		 * @param int $from
		 * @param int $count
		 * @return array
		 */
		public static function getMessages($user_id, $account_id, $folder_id, $from = 0, $count = 20) {
			// get the account
			$account = App::getAccount($user_id, $account_id);
			if (!$account) {
				//@TODO: i18n
				return array('error' => 'unknown account');
			}

			try {
				/** @var $mailbox \OCA\Mail\Mailbox */
				$mailbox = $account->getMailbox($folder_id);
				$messages = $mailbox->getMessages($from, $count);

				return array('account_id' => $account_id, 'folder_id' => $folder_id, 'messages' => $messages);
			} catch (\Horde_Imap_Client_Exception $e) {
				return array('error' => $e->getMessage());
			}
		}

		/**
		 * @static
		 * @param $user_id
		 * @param $account_id
		 * @param $folder_id
		 * @param $message_id
		 * @return array
		 */
		public static function getMessage($user_id, $account_id, $folder_id, $message_id) {
			// get the account
			$account = App::getAccount($user_id, $account_id);
			if (!$account) {
				//@TODO: i18n
				return array('error' => 'unknown account');
			}

			try {
				/** @var $mailbox \OCA\Mail\Mailbox */
				$mailbox = $account->getMailbox($folder_id);
				$m = $mailbox->getMessage($message_id);
				$message = $m->as_array();

				// add sender image
				$message['sender_image'] = self::getPhoto($m->getFromEmail());

				return array('message' => $message);
			} catch (\Horde_Imap_Client_Exception $e) {
				return array('error' => $e->getMessage());
			}
		}

		public static function getPhoto($email) {
			$result = \OCP\Contacts::search($email, array('EMAIL'));
			if (count($result) > 0) {
				if (isset($result[0]['PHOTO'])) {
					$s = $result[0]['PHOTO'];
					return substr($s, strpos($s, 'http'));
				}
			}
			return \OCP\Util::imagePath('mail', 'person.png');
		}

		/**
		 * @param $user_id
		 * @return Account[]
		 */
		private static function getAccounts($user_id) {
			$account_ids = \OCP\Config::getUserValue($user_id, 'mail', 'accounts', '');
			if ($account_ids == "") {
				return array();
			}

			$account_ids = explode(',', $account_ids);

			$accounts = array();
			foreach ($account_ids as $id) {
				$account_string = 'account[' . $id . ']';

				$accounts[$id] = new Account(array(
					'id'       => $id,
					'name'     => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[name]'),
					'email'    => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[email]'),
					'host'     => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[host]'),
					'port'     => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[port]'),
					'user'     => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[user]'),
					'password' => base64_decode(\OCP\Config::getUserValue($user_id, 'mail', $account_string . '[password]')),
					'ssl_mode' => \OCP\Config::getUserValue($user_id, 'mail', $account_string . '[ssl_mode]')
				));
			}

			return $accounts;
		}

		/**
		 * @param $user_id
		 * @param $account_id
		 * @return Account
		 */
		public static function getAccount($user_id, $account_id) {
			$accounts = App::getAccounts($user_id);

			if (isset($accounts[$account_id])) {
				return $accounts[$account_id];
			}

			return null;
		}

		public static function addAccount($user_id, $email, $host, $port, $user, $password, $ssl_mode) {
			$id = time();
			$account_string = 'account[' . $id . ']';
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[name]', $user);
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[email]', $email);
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[host]', $host);
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[port]', $port);
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[user]', $user);
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[password]', base64_encode($password));
			\OCP\Config::setUserValue($user_id, 'mail', $account_string . '[ssl_mode]', $ssl_mode);

			$account_ids = \OCP\Config::getUserValue($user_id, 'mail', 'accounts', '');
			if ($account_ids) {
				$account_ids = explode(',', $account_ids);
			} else {
				$account_ids = array();
			}
			$account_ids[] = $id;
			$account_ids = implode(",", $account_ids);

			\OCP\Config::setUserValue($user_id, 'mail', 'accounts', $account_ids);

			return $id;
		}

		public static function autoDetectAccount($user_id, $email, $password) {
			list($user, $host) = explode("@", $email);

			//
			// google apps shortcut
			//
			if (self::isGoogleAppsAccount($host)) {
				$new_account = self::testAccount($user_id, $email, "imap.gmail.com", $email, $password);
				if ($new_account != null) {
					return $new_account;
				}
			}
			$new_account = self::testAccount($user_id, $email, $host, $user, $password);

			// try full email address as user name now (e.g. gmail does so)
			if ($new_account == null) {
				$new_account = self::testAccount($user_id, $email, $host, $email, $password);
			}

			return $new_account;
		}

		private static function isGoogleAppsAccount($host) {
			// filter pure gmail accounts
			if (stripos($host, 'google') !== false) {
				return false;
			}
			if (stripos($host, 'gmail') !== false) {
				return false;
			}

			//
			// TODO: will not work on windows - ignore this for now
			//
			if (getmxrr($host, $mx_records, $mx_weight) == false)
			{
				return false;
			}

			if (stripos($mx_records[0], 'google') !== false) {
				return true;
			}
			return false;
		}

		private static function testAccount($user_id, $email, $host, $user, $password) {
			/*
			IMAP - port 143
			Secure IMAP (IMAP4-SSL) - port 585
			IMAP4 over SSL (IMAPS) - port 993
			 */
			$account = array(
				'name'     => $email,
				'host'     => $host,
				'user'     => $user,
				'password' => $password,
			);

			$ports = array(143, 585, 993);
			$sec_modes = array('ssl', 'tls', null);
			$host_prefixes = array('', 'imap.');
			foreach ($host_prefixes as $host_prefix) {
				$h = $host_prefix . $host;
				$account['host'] = $h;
				foreach ($ports as $port) {
					$account['port'] = $port;
					foreach ($sec_modes as $sec_mode) {
						$account['ssl_mode'] = $sec_mode;
						try {
							$test_account = new Account($account);
							$client = $test_account->getImapConnection();
							error_log("Successful: $user_id, $h, $port, $user, $sec_mode");
							return App::addAccount($user_id, $email, $h, $port, $user, $password, $sec_mode);
						} catch (\Horde_Imap_Client_Exception $e) {
							// nothing to do
							error_log("Failed: $user_id, $h, $port, $user, $sec_mode");
						}
					}
				}
			}

			return null;
		}
	}
}
