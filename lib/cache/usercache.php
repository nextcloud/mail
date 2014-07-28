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

namespace OCA\Mail\Cache;

use Horde_Imap_Client_Cache_Backend;

class UserCache extends Horde_Imap_Client_Cache_Backend {

	/**
	 * @var \OCP\ICache
	 */
	private $userCache;

	public function __construct(array $params = array())
	{
		parent::__construct($params);

		$this->userCache = \OC::$server->getCache();
	}

	/**
	 * Get information from the cache for a set of UIDs.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param array $uids The list of message UIDs to retrieve
	 *                           information for.
	 * @param array $fields An array of fields to retrieve. If empty,
	 *                           returns all cached fields.
	 * @param integer $uidvalid The IMAP uidvalidity value of the mailbox.
	 *
	 * @return array  An array of arrays with the UID of the message as the
	 *                key (if found) and the fields as values (will be
	 *                undefined if not found).
	 */
	public function get($mailbox, $uids, $fields, $uidvalid) {
		$uid = $this->buildUid($mailbox);

		$data = $this->userCache->get($uid);
		$json = json_decode($data, true);
		if (is_null($json)) {
			return array();
		}
		return $json;
	}

	/**
	 * Get the list of cached UIDs.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param integer $uidvalid The IMAP uidvalidity value of the mailbox.
	 *
	 * @return array  The (unsorted) list of cached UIDs.
	 */
	public function getCachedUids($mailbox, $uidvalid) {
		return array();
	}

	/**
	 * Store data in cache.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param array $data The list of data to save. The keys are the
	 *                           UIDs, the values are an array of information
	 *                           to save.
	 * @param integer $uidvalid The IMAP uidvalidity value of the mailbox.
	 */
	public function set($mailbox, $data, $uidvalid) {
		$uid = $this->buildUid($mailbox);
		$res = $this->get($mailbox, array_keys($data), array(), $uidvalid);

		$mergedData = array_merge($res, $data);
		$this->userCache->set($uid, json_encode($mergedData));
	}

	/**
	 * Get metadata information for a mailbox.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param integer $uidvalid The IMAP uidvalidity value of the mailbox.
	 * @param array $entries An array of entries to return. If empty,
	 *                           returns all metadata.
	 *
	 * @return array  The requested metadata. Requested entries that do not
	 *                exist will be undefined. The following entries are
	 *                defaults and always present:
	 *   - uidvalid: (integer) The UIDVALIDITY of the mailbox.
	 */
	public function getMetaData($mailbox, $uidvalid, $entries) {
		return array();
	}

	/**
	 * Set metadata information for a mailbox.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param array $data The list of data to save. The keys are the
	 *                           metadata IDs, the values are the associated
	 *                           data. (If present, uidvalidity appears as
	 *                           the 'uidvalid' key in $data.)
	 */
	public function setMetaData($mailbox, $data) {
		// TODO: Implement setMetaData() method.
	}

	/**
	 * Delete messages in the cache.
	 *
	 * @param string $mailbox An IMAP mailbox string.
	 * @param array $uids The list of message UIDs to delete.
	 */
	public function deleteMsgs($mailbox, $uids) {
		// TODO: Implement deleteMsgs() method.
	}

	/**
	 * Delete a mailbox from the cache.
	 *
	 * @param string $mailbox The mailbox to delete.
	 */
	public function deleteMailbox($mailbox) {
		// TODO: Implement deleteMailbox() method.
	}

	/**
	 * Clear the cache.
	 *
	 * @param integer $lifetime Only delete entries older than this (in
	 *                           seconds). If null, deletes all entries.
	 */
	public function clear($lifetime) {
		// TODO: Implement clear() method.
	}

	private function buildUid($mailbox) {
		$data = array(
			'hostspec' => $this->_params['hostspec'],
			'mailbox' => $mailbox,
			'port' => $this->_params['port'],
			'username' => $this->_params['username']
		);

		return md5(implode($data));
	}

}
