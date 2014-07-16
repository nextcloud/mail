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
//	$incPath = __DIR__."/../3rdparty";
//	set_include_path(get_include_path() . PATH_SEPARATOR . $incPath);

	// load Horde's auto loader
//	require_once 'Horde/Autoloader/Default.php';
	require __DIR__ . '/../vendor/autoload.php';

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

	}
}
