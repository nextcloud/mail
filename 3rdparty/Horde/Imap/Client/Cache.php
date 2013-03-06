<?php
/**
 * An interface to cache various data retrieved from the IMAP server.
 * Requires the Horde_Cache package.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Cache
{
    /** Cache structure version. */
    const VERSION = 3;

    /**
     * The base driver object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_base;

    /**
     * The cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * The working data for the current pageload.  All changes take place to
     * this data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * The list of cache slices loaded.
     *
     * @var array
     */
    protected $_loaded = array();

    /**
     * The configuration params.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The mapping of UIDs to slices.
     *
     * @var array
     */
    protected $_slicemap = array();

    /**
     * The list of items to update:
     *   - add: (array) List of IDs that were added.
     *   - slice: (array) List of slices that were modified.
     *   - slicemap: (boolean) Was slicemap info changed?
     *
     * @var array
     */
    protected $_update = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <ul>
     *  <li>
     *   REQUIRED Parameters:
     *   <ul>
     *    <li>
     *     baseob: (Horde_Imap_Client_Base) The driver object.
     *    </li>
     *    <li>
     *     cacheob: (Horde_Cache) The cache object to use.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Optional Parameters:
     *   <ul>
     *    <li>
     *     debug: (boolean) If true, will output debug information.
     *            DEFAULT: No debug output
     *    </li>
     *    <li>
     *     lifetime: (integer) The lifetime of the cache data (in seconds).
     *               DEFAULT: 1 week (604800 seconds)
     *    </li>
     *    <li>
     *     slicesize: (integer) The slicesize to use.
     *                DEFAULT: 50
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     */
    public function __construct(array $params = array())
    {
        $required = array('baseob', 'cacheob');
        foreach ($required as $val) {
            if (empty($params[$val])) {
                throw new InvalidArgumentException('Missing required parameter for ' . __CLASS__ . ': ' . $val);
            }
        }

        // Default parameters.
        $params = array_merge(array(
            'debug' => false,
            'lifetime' => 604800,
            'slicesize' => 50
        ), array_filter($params));

        $this->_base = $params['baseob'];
        $this->_params = array(
            'hostspec' => $this->_base->getParam('hostspec'),
            'port' => $this->_base->getParam('port'),
            'username' => $this->_base->getParam('username')
        );

        $this->_cache = $params['cacheob'];

        $this->_params['debug'] = (bool)$params['debug'];
        $this->_params['lifetime'] = intval($params['lifetime']);
        $this->_params['slicesize'] = intval($params['slicesize']);

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Updates the cache on shutdown.
     */
    public function shutdown()
    {
        $lifetime = $this->_params['lifetime'];

        foreach ($this->_update as $mbox => $val) {
            $s = &$this->_slicemap[$mbox];

            if (!empty($val['add'])) {
                if ($s['c'] <= $this->_params['slicesize']) {
                    $val['slice'][] = $s['i'];
                    $this->_loadSlice($mbox, $s['i']);
                }
                $val['slicemap'] = true;

                foreach (array_keys(array_flip($val['add'])) as $uid) {
                    if ($s['c']++ > $this->_params['slicesize']) {
                        $s['c'] = 0;
                        $val['slice'][] = ++$s['i'];
                        $this->_loadSlice($mbox, $s['i']);
                    }
                    $s['s'][$uid] = $s['i'];
                }
            }

            if (!empty($val['slice'])) {
                $d = &$this->_data[$mbox];
                $val['slicemap'] = true;

                foreach (array_keys(array_flip($val['slice'])) as $slice) {
                    $data = array();
                    foreach (array_keys($s['s'], $slice) as $uid) {
                        $data[$uid] = is_array($d[$uid])
                            ? serialize($d[$uid])
                            : $d[$uid];
                    }
                    $this->_cache->set($this->_getCid($mbox, $slice), serialize($data), $lifetime);
                }
            }

            if (!empty($val['slicemap'])) {
                $this->_cache->set($this->_getCid($mbox, 'slicemap'), serialize($s), $lifetime);
            }
        }
    }

    /**
     * Create the unique ID used to store the data in the cache.
     *
     * @param string $mailbox  The mailbox to cache.
     * @param string $slice    The cache slice.
     *
     * @return string  The cache ID.
     */
    protected function _getCid($mailbox, $slice)
    {
        return implode('|', array(
            'horde_imap_client',
            $this->_params['username'],
            $mailbox,
            $this->_params['hostspec'],
            $this->_params['port'],
            $slice,
            self::VERSION
        ));
    }

    /**
     * Get information from the cache.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param array $uids        The list of message UIDs to retrieve
     *                           information for. If empty, returns the list
     *                           of cached UIDs.
     * @param array $fields      An array of fields to retrieve. If null,
     *                           returns all cached fields.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     *
     * @return array  An array of arrays with the UID of the message as the
     *                key (if found) and the fields as values (will be
     *                undefined if not found). If $uids is empty, returns the
     *                full (unsorted) list of cached UIDs.
     */
    public function get($mailbox, array $uids = array(), $fields = array(),
                        $uidvalid = null)
    {
        $mailbox = strval($mailbox);

        if (empty($uids)) {
            $this->_loadSliceMap($mailbox, $uidvalid);
            return array_keys($this->_slicemap[$mailbox]['s']);
        }

        $ret = array();
        $this->_loadUids($mailbox, $uids, $uidvalid);

        if (empty($this->_data[$mailbox])) {
            return $ret;
        }

        if (!is_null($fields)) {
            $fields = array_flip($fields);
        }
        $ptr = &$this->_data[$mailbox];

        foreach (array_intersect($uids, array_keys($ptr)) as $val) {
            if (is_string($ptr[$val])) {
                $ptr[$val] = @unserialize($ptr[$val]);
            }

            $ret[$val] = (is_null($fields) || empty($ptr[$val]))
                ? $ptr[$val]
                : array_intersect_key($ptr[$val], $fields);
        }

        if ($this->_params['debug'] && !empty($ret)) {
            $this->_base->writeDebug('CACHE: Retrieved messages (mailbox: ' . $mailbox . '; UIDs: ' . $this->_base->getIdsOb(array_keys($ret))->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
        }

        return $ret;
    }

    /**
     * Store information in cache.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param array $data        The list of data to save. The keys are the
     *                           UIDs, the values are an array of information
     *                           to save. If empty, do a check to make sure
     *                           the uidvalidity is still valid.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    public function set($mailbox, $data, $uidvalid)
    {
        $mailbox = strval($mailbox);

        if (empty($data)) {
            $this->_loadSliceMap($mailbox, $uidvalid);
            return;
        }

        $update = array_keys($data);

        try {
            $this->_loadUids($mailbox, $update, $uidvalid);
        } catch (Horde_Imap_Client_Exception $e) {
            // Ignore invalidity - just start building the new cache
        }

        $d = &$this->_data[$mailbox];
        $s = &$this->_slicemap[$mailbox]['s'];
        $add = $updated = array();

        foreach ($data as $k => $v) {
            if (isset($d[$k])) {
                if (is_string($d[$k])) {
                    $d[$k] = @unserialize($d[$k]);
                }
                $d[$k] = is_array($d[$k])
                    ? array_merge($d[$k], $v)
                    : $v;
                if (isset($s[$k])) {
                    $updated[$s[$k]] = true;
                }
            } else {
                $d[$k] = $v;
                $add[] = $k;
            }
        }

        $this->_toUpdate($mailbox, 'add', $add);
        $this->_toUpdate($mailbox, 'slice', array_keys($updated));

        if ($this->_params['debug']) {
            $this->_base->writeDebug('CACHE: Stored messages (mailbox: ' . $mailbox . '; UIDs: ' . $this->_base->getIdsOb($update)->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
        }
    }

    /**
     * Get metadata information for a mailbox.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     * @param array $entries     An array of entries to return. If empty,
     *                           returns all metadata.
     *
     * @return array  The requested metadata. Requested entries that do not
     *                exist will be undefined. The following entries are
     *                defaults and always present:
     *   - uidvalid: (integer) The UIDVALIDITY of the mailbox.
     */
    public function getMetaData($mailbox, $uidvalid = null,
                                array $entries = array())
    {
        $mailbox = strval($mailbox);
        $this->_loadSliceMap($mailbox, $uidvalid);

        return empty($entries)
            ? $this->_slicemap[$mailbox]['d']
            : array_intersect_key($this->_slicemap[$mailbox]['d'], array_flip($entries));
    }

    /**
     * Set metadata information for a mailbox.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     * @param array $data        The list of data to save. The keys are the
     *                           metadata IDs, the values are the associated
     *                           data. The following labels are reserved:
     *                           'uidvalid'.
     */
    public function setMetaData($mailbox, $uidvalid, array $data = array())
    {
        unset($data['uidvalid']);
        if (empty($data)) {
            return;
        }

        $mailbox = strval($mailbox);
        $this->_loadSliceMap($mailbox, $uidvalid);
        $this->_slicemap[$mailbox]['d'] = array_merge($this->_slicemap[$mailbox]['d'], $data);
        $this->_toUpdate($mailbox, 'slicemap', true);

        if ($this->_params['debug']) {
            $this->_base->writeDebug('CACHE: Stored metadata (mailbox: ' . $mailbox . '; Keys: ' . implode(',', array_keys($data)) . ")\n", Horde_Imap_Client::DEBUG_INFO);
        }
    }

    /**
     * Delete messages in the cache.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $uids      The list of message UIDs to delete.
     */
    public function deleteMsgs($mailbox, $uids)
    {
        if (empty($uids)) {
            return;
        }

        $mailbox = strval($mailbox);
        $this->_loadSliceMap($mailbox);

        $slicemap = &$this->_slicemap[$mailbox];
        $update = array_intersect_key($slicemap['s'], array_flip(is_array($uids) ? $uids : iterator_to_array($uids)));

        if (!empty($update)) {
            $this->_loadUids($mailbox, array_keys($update));
            $d = &$this->_data[$mailbox];

            foreach (array_keys($update) as $id) {
                unset($d[$id], $slicemap['s'][$id]);
            }

            foreach (array_unique($update) as $slice) {
                /* Get rid of slice if less than 10% of capacity. */
                if (($slice != $slicemap['i']) &&
                    ($slice_uids = array_keys($slicemap['s'], $slice)) &&
                    ($this->_params['slicesize'] * 0.1) > count($slice_uids)) {
                    $this->_toUpdate($mailbox, 'add', $slice_uids);
                    $this->_cache->expire($this->_getCid($mailbox, $slice));
                    foreach ($slice_uids as $val) {
                        unset($slicemap['s'][$val]);
                    }
                } else {
                    $this->_toUpdate($mailbox, 'slice', array($slice));
                }
            }

            if ($this->_params['debug']) {
                $this->_base->writeDebug('CACHE: Deleted messages (mailbox: ' . $mailbox . '; UIDs: ' . $this->_base->getIdsOb(array_keys($update))->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
            }
        }
    }

    /**
     * Delete a mailbox from the cache.
     *
     * @param string $mbox  The mailbox to delete.
     */
    public function deleteMailbox($mbox)
    {
        $mbox = strval($mbox);
        $this->_loadSliceMap($mbox);
        $this->_deleteMailbox($mbox);
    }

    /**
     * Delete a mailbox from the cache.
     *
     * @param string $mbox  The mailbox to delete.
     */
    protected function _deleteMailbox($mbox)
    {
        foreach (array_merge(array_keys(array_flip($this->_slicemap[$mbox]['s'])), array('slicemap')) as $slice) {
            $cid = $this->_getCid($mbox, $slice);
            $this->_cache->expire($cid);
            unset($this->_loaded[$cid]);
        }

        unset(
            $this->_data[$mbox],
            $this->_slicemap[$mbox],
            $this->_update[$mbox]
        );

        if ($this->_params['debug']) {
            $this->_base->writeDebug('CACHE: Deleted mailbox (mailbox: ' . $mbox . ")\n", Horde_Imap_Client::DEBUG_INFO);
        }
    }

    /**
     * Load UIDs by regenerating from the cache.
     *
     * @param string $mailbox    The mailbox to load.
     * @param array $uids        The UIDs to load.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadUids($mailbox, $uids, $uidvalid = null)
    {
        if (!isset($this->_data[$mailbox])) {
            $this->_data[$mailbox] = array();
        }

        $this->_loadSliceMap($mailbox, $uidvalid);

        if (!empty($uids)) {
            foreach (array_unique(array_intersect_key($this->_slicemap[$mailbox]['s'], array_flip($uids))) as $slice) {
                $this->_loadSlice($mailbox, $slice);
            }
        }
    }

    /**
     * Load UIDs from a cache slice.
     *
     * @param string $mailbox  The mailbox to load.
     * @param integer $slice   The slice to load.
     */
    protected function _loadSlice($mailbox, $slice)
    {
        $cache_id = $this->_getCid($mailbox, $slice);

        if (!empty($this->_loaded[$cache_id])) {
            return;
        }

        if ((($data = $this->_cache->get($cache_id, $this->_params['lifetime'])) !== false) &&
            ($data = @unserialize($data)) &&
            is_array($data)) {
            $this->_data[$mailbox] += $data;
            $this->_loaded[$cache_id] = true;
        } else {
            $ptr = &$this->_slicemap[$mailbox];

            // Slice data is corrupt; remove from slicemap.
            foreach (array_keys($ptr['s'], $slice) as $val) {
                unset($ptr['s'][$val]);
            }

            if ($slice == $ptr['i']) {
                $ptr['c'] = 0;
            }
        }
    }

    /**
     * Load the slicemap for a given mailbox.  The slicemap contains
     * the uidvalidity information, the UIDs->slice lookup table, and any
     * metadata that needs to be saved for the mailbox.
     *
     * @param string $mailbox    The mailbox.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadSliceMap($mailbox, $uidvalid = null)
    {
        if (!isset($this->_slicemap[$mailbox]) &&
            (($data = $this->_cache->get($this->_getCid($mailbox, 'slicemap'), $this->_params['lifetime'])) !== false) &&
            ($slice = @unserialize($data)) &&
            is_array($slice)) {
            $this->_slicemap[$mailbox] = $slice;
        }

        if (isset($this->_slicemap[$mailbox])) {
            $ptr = &$this->_slicemap[$mailbox];
            if (is_null($ptr['d']['uidvalid'])) {
                $ptr['d']['uidvalid'] = $uidvalid;
                return;
            } elseif (!is_null($uidvalid) &&
                      ($ptr['d']['uidvalid'] != $uidvalid)) {
                $this->_deleteMailbox($mailbox);
            } else {
                return;
            }
        }

        $this->_slicemap[$mailbox] = array(
            // Tracking count for purposes of determining slices
            'c' => 0,
            // Metadata storage
            // By default includes UIDVALIDITY of mailbox.
            'd' => array('uidvalid' => $uidvalid),
            // The ID of the last slice.
            'i' => 0,
            // The slice list.
            's' => array()
        );
    }

    /**
     * Add update entry for a mailbox.
     *
     * @param string $mailbox  The mailbox.
     * @param string $type     'add', 'slice', or 'slicemap'.
     * @param mixed $data      The data to update.
     */
    protected function _toUpdate($mailbox, $type, $data)
    {
        if (!isset($this->_update[$mailbox])) {
            $this->_update[$mailbox] = array(
                'add' => array(),
                'slice' => array()
            );
        }

        $this->_update[$mailbox][$type] = ($type == 'slicemap')
            ? $data
            : array_merge($this->_update[$mailbox][$type], $data);
    }

}
