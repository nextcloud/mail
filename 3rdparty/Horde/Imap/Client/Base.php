<?php
/**
 * An abstracted API interface to IMAP backends supporting the IMAP4rev1
 * protocol (RFC 3501).
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
abstract class Horde_Imap_Client_Base implements Serializable
{
    /* Serialized version. */
    const VERSION = 2;

    /* Cache names for miscellaneous data. */
    const CACHE_MODSEQ = 'HICmodseq';
    const CACHE_SEARCH = 'HICsearch';

    /**
     * The list of fetch fields that can be cached, and their cache names.
     *
     * @var array
     */
    public $cacheFields = array(
        Horde_Imap_Client::FETCH_ENVELOPE => 'HICenv',
        Horde_Imap_Client::FETCH_FLAGS => 'HICflags',
        Horde_Imap_Client::FETCH_HEADERS => 'HIChdrs',
        Horde_Imap_Client::FETCH_IMAPDATE => 'HICdate',
        Horde_Imap_Client::FETCH_SIZE => 'HICsize',
        Horde_Imap_Client::FETCH_STRUCTURE => 'HICstruct'
    );

    /**
     * Has the internal configuration changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * The Horde_Imap_Client_Cache object.
     *
     * @var Horde_Imap_Client_Cache
     */
    protected $_cache = null;

    /**
     * The debug stream.
     *
     * @var resource
     */
    protected $_debug = null;

    /**
     * The fetch data object type to return.
     *
     * @var string
     */
    protected $_fetchDataClass = 'Horde_Imap_Client_Data_Fetch';

    /**
     * Cached server data.
     *
     * @var array
     */
    protected $_init;

    /**
     * Is there an active authenticated connection to the IMAP Server?
     *
     * @var boolean
     */
    protected $_isAuthenticated = false;

    /**
     * Is there a secure connection to the IMAP Server?
     *
     * @var boolean
     */
    protected $_isSecure = false;

    /**
     * The current mailbox selection mode.
     *
     * @var integer
     */
    protected $_mode = 0;

    /**
     * Hash containing connection parameters.
     * This hash never changes.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The currently selected mailbox.
     *
     * @var Horde_Imap_Client_Mailbox
     */
    protected $_selected = null;

    /**
     * Temp array (destroyed at end of process).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * Constructor.
     *
     * @param array $params   Configuration parameters:
     * <ul>
     *  <li>REQUIRED Parameters
     *   <ul>
     *    <li>password: (string) The IMAP user password.</li>
     *    <li>username: (string) The IMAP username.</li>
     *   </ul>
     *  </li>
     *  <li>Optional Parameters
     *   <ul>
     *    <li>
     *     cache: (array) If set, caches data from fetch(), search(), and
     *            thread() calls. Requires the horde/Cache package to be
     *            installed. The array can contain the following keys (see
     *            Horde_Imap_Client_Cache for default values):
     *     <ul>
     *      <li>
     *       cacheob: [REQUIRED] (Horde_Cache) The cache object to
     *                use.
     *      </li>
     *      <li>
     *       fetch_ignore: (array) A list of mailboxes to ignore when storing
     *                     fetch data.
     *      </li>
     *      <li>
     *       fields: (array) The fetch criteria to cache. If not defined, all
     *               cacheable data is cached. The following is a list of
     *               criteria that can be cached:
     *       <ul>
     *        <li>Horde_Imap_Client::FETCH_ENVELOPE</li>
     *        <li>Horde_Imap_Client::FETCH_FLAGS
     *         <ul>
     *          <li>
     *           Only if server supports CONDSTORE extension
     *          </li>
     *         </ul>
     *        </li>
     *        <li>Horde_Imap_Client::FETCH_HEADERS
     *         <ul>
     *          <li>
     *           Only for queries that specifically request caching
     *          </li>
     *         </ul>
     *        </li>
     *        <li>Horde_Imap_Client::FETCH_IMAPDATE</li>
     *        <li>Horde_Imap_Client::FETCH_SIZE</li>
     *        <li>Horde_Imap_Client::FETCH_STRUCTURE</li>
     *       </ul>
     *      </li>
     *      <li>
     *       lifetime: (integer) Lifetime of the cache data (in seconds).
     *      </li>
     *      <li>
     *       slicesize: (integer) The slicesize to use.
     *      </li>
     *     </ul>
     *    </li>
     *    <li>
     *     capability_ignore: (array) A list of IMAP capabilites to ignore,
     *                        even if they are supported on the server.
     *                        DEFAULT: No supported capabilities are ignored.
     *    </li>
     *    <li>
     *     comparator: (string) The search comparator to use instead of the
     *                 default IMAP server comparator. See
     *                 Horde_Imap_Client_Base#setComparator() for format.
     *                 DEFAULT: Use the server default
     *    </li>
     *    <li>
     *     debug: (string) If set, will output debug information to the stream
     *            provided. The value can be any PHP supported wrapper that
     *            can be opened via fopen().
     *            DEFAULT: No debug output
     *    </li>
     *    <li>
     *     encryptKey: (array) A callback to a function that returns the key
     *                 used to encrypt the password. This function MUST be
     *                 static.
     *                 DEFAULT: No encryption
     *    </li>
     *    <li>
     *     hostspec: (string) The hostname or IP address of the server.
     *               DEFAULT: 'localhost'
     *    </li>
     *    <li>
     *     id: (array) Send ID information to the IMAP server (only if server
     *         supports the ID extension). An array with the keys as the
     *         fields to send and the values being the associated values. See
     *         RFC 2971 [3.3] for a list of defined standard field values.
     *         DEFAULT: No info sent to server
     *    </li>
     *    <li>
     *     lang: (array) A list of languages (in priority order) to be used to
     *           display human readable messages.
     *           DEFAULT: Messages output in IMAP server default language
     *    </li>
     *    <li>
     *     port: (integer) The server port to which we will connect.
     *           DEFAULT: 143 (imap or imap w/TLS) or 993 (imaps)
     *    </li>
     *    <li>
     *     secure: (string) Use SSL or TLS to connect.
     *             VALUES:
     *     <ul>
     *      <li>false</li>
     *      <li>'ssl' (Auto-detect SSL version)</li>
     *      <li>'sslv2' (Force SSL version 3)</li>
     *      <li>'sslv3' (Force SSL version 2)</li>
     *      <li>'tls'</li>
     *     </ul>
     *             DEFAULT: No encryption</li>
     *    </li>
     *    <li>
     *     timeout: (integer)  Connection timeout, in seconds.
     *              DEFAULT: 30 seconds
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['username']) || !isset($params['password'])) {
            throw new InvalidArgumentException('Horde_Imap_Client requires a username and password.');
        }

        $this->_setInit();

        // Default values.
        $params = array_merge(array(
            'encryptKey' => null,
            'hostspec' => 'localhost',
            'secure' => false,
            'timeout' => 30
        ), array_filter($params));

        if ($params['secure'] === true) {
            $params['secure'] = 'tls';
        }

        if (!isset($params['port'])) {
            $params['port'] = (isset($params['secure']) && in_array($params['secure'], array('ssl', 'sslv2', 'sslv3')))
                ? 993
                : 143;
        }

        if (empty($params['cache'])) {
            $params['cache'] = array('fields' => array());
        } elseif (empty($params['cache']['fields'])) {
            $params['cache']['fields'] = $this->cacheFields;
        } else {
            $params['cache']['fields'] = array_flip($params['cache']['fields']);
        }

        if (empty($params['cache']['fetch_ignore'])) {
            $params['cache']['fetch_ignore'] = array();
        }

        $this->_params = $params;
        $this->setParam('password', $this->_params['password']);

        $this->changed = true;
        $this->_initOb();
    }

    /**
     * Get encryption key.
     *
     * @return string  The encryption key.
     */
    protected function _getEncryptKey()
    {
        if (is_callable($this->_params['encryptKey'])) {
            return call_user_func($this->_params['encryptKey']);
        }

        throw new InvalidArgumentException('encryptKey parameter is not a valid callback.');
    }

    /**
     * Do initialization tasks.
     */
    protected function _initOb()
    {
        if (!empty($this->_params['debug'])) {
            $this->_debug = is_resource($this->_params['debug'])
                ? $this->_params['debug']
                :  @fopen($this->_params['debug'], 'a');
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->logout();

        /* Close debugging output. */
        if (is_resource($this->_debug)) {
            fflush($this->_debug);
            fclose($this->_debug);
            $this->_debug = null;
        }
    }

    /**
     */
    public function serialize()
    {
        return serialize(array(
            'i' => $this->_init,
            'p' => $this->_params,
            'v' => self::VERSION
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data['v']) ||
            ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_init = $data['i'];
        $this->_params = $data['p'];

        $this->_initOb();
    }

    /**
     * Set an initialization value.
     *
     * @param string $key  The initialization key. If null, resets all keys.
     * @param mixed $val   The cached value. If null, removes the key.
     */
    public function _setInit($key = null, $val = null)
    {
        if (is_null($key)) {
            $this->_init = array(
                'enabled' => array(),
                'namespace' => array(),
                's_charset' => array()
            );
        } elseif (is_null($val)) {
            unset($this->_init[$key]);
        } else {
            switch ($key) {
            case 'capability':
                if (!empty($this->_params['capability_ignore'])) {
                    if ($this->_debug &&
                        ($ignored = array_intersect_key($val, array_flip($this->_params['capability_ignore'])))) {
                        $this->writeDebug(sprintf("IGNORING these IMAP capabilities: %s\n", implode(', ', array_keys($ignored))), Horde_Imap_Client::DEBUG_INFO);
                    }

                    $val = array_diff_key($val, array_flip($this->_params['capability_ignore']));
                }

                /* RFC 5162 [1] - QRESYNC implies CONDSTORE and ENABLE, even
                 * if not listed as a capability. */
                if (!empty($val['QRESYNC'])) {
                    $val['CONDSTORE'] = true;
                    $val['ENABLE'] = true;
                }
                break;

            case 'enabled':
                /* RFC 5162 [1] - Enabling QRESYNC also implies enabling of
                 * CONDSTORE. */
                if (isset($val['QRESYNC'])) {
                    $val['CONDSTORE'] = true;
                }
                break;
            }

            $this->_init[$key] = $val;
        }
        $this->changed = true;
    }

    /**
     * Initialize the Horde_Imap_Client_Cache object, if necessary.
     *
     * @param boolean $current  If true, we are going to update the currently
     *                          selected mailbox. Add an additional check to
     *                          see if caching is available in current
     *                          mailbox.
     *
     * @return boolean  Returns true if caching is enabled.
     */
    protected function _initCache($current = false)
    {
        if (empty($this->_params['cache']['fields']) ||
            !empty($this->_temp['nocache'])) {
            return false;
        }

        if (is_null($this->_cache)) {
            try {
                $this->_cache = new Horde_Imap_Client_Cache(array_merge($this->getParam('cache'), array(
                    'baseob' => $this,
                    'debug' => (bool)$this->_debug
                )));
            } catch (InvalidArgumentException $e) {
                return false;
            }
        }

        if (!$current) {
            return true;
        }

        /* If UIDs are labeled as not sticky, don't cache since UIDs will
         * change on every access. */
        $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
        return !$status['uidnotsticky'];
    }

    /**
     * Returns a value from the internal params array.
     *
     * @param string $key  The param key.
     *
     * @return mixed  The param value, or null if not found.
     */
    public function getParam($key)
    {
        /* Passwords may be stored encrypted. */
        if (($key == 'password') && !empty($this->_params['_passencrypt'])) {
            try {
                $secret = new Horde_Secret();
                return $secret->read($this->_getEncryptKey(), $this->_params['password']);
            } catch (Exception $e) {
                return null;
            }
        }

        return isset($this->_params[$key])
            ? $this->_params[$key]
            : null;
    }

    /**
     * Sets a configuration parameter value.
     *
     * @param string $key   The param key.
     * @param mixed $value  The param value.
     */
    public function setParam($key, $val)
    {
        switch ($key) {
        case 'password':
            // Encrypt password.
            try {
                $encrypt_key = $this->_getEncryptKey();
                if (strlen($encrypt_key)) {
                    $secret = new Horde_Secret();
                    $val = $secret->write($encrypt_key, $val);
                    $this->_params['_passencrypt'] = true;
                }
            } catch (Exception $e) {
                $this->_params['_passencrypt'] = false;
            }
            break;
        }

        $this->_params[$key] = $val;
        $this->changed = true;
    }

    /**
     * Returns the Horde_Imap_Client_Cache object used, if available.
     *
     * @return mixed  Either the cache object or null.
     */
    public function getCache()
    {
        $this->_initCache();
        return $this->_cache;
    }

    /**
     * Returns the correct IDs object for use with this driver.
     *
     * @param mixed $ids         See add().
     * @param boolean $sequence  Are $ids message sequence numbers?
     *
     * @return Horde_Imap_Client_Ids  The IDs object.
     */
    public function getIdsOb($ids = null, $sequence = false)
    {
        return new Horde_Imap_Client_Ids($ids, $sequence);
    }

    /**
     * Returns whether the IMAP server supports the given capability
     * (See RFC 3501 [6.1.1]).
     *
     * @param string $capability  The capability string to query.
     *
     * @param mixed  True if the server supports the queried capability,
     *               false if it doesn't, or an array if the capability can
     *               contain multiple values.
     */
    public function queryCapability($capability)
    {
        if (!isset($this->_init['capability'])) {
            try {
                $this->capability();
            } catch (Horde_Imap_Client_Exception $e) {
                return false;
            }
        }

        $capability = strtoupper($capability);

        if (!isset($this->_init['capability'][$capability])) {
            return false;
        }

        /* Check for capability requirements. */
        if (isset(Horde_Imap_Client::$capability_deps[$capability])) {
            foreach (Horde_Imap_Client::$capability_deps[$capability] as $val) {
                if (!$this->queryCapability($val)) {
                    return false;
                }
            }
        }

        return $this->_init['capability'][$capability];
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function capability()
    {
        if (!isset($this->_init['capability'])) {
            $this->_setInit('capability', $this->_capability());
        }

        return $this->_init['capability'];
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _capability();

    /**
     * Send a NOOP command (RFC 3501 [6.1.2]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function noop()
    {
        // NOOP only useful if we are already authenticated.
        if ($this->_isAuthenticated) {
            $this->_noop();
        }
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _noop();

    /**
     * Get the NAMESPACE information from the IMAP server (RFC 2342).
     *
     * @param array $additional  If the server supports namespaces, any
     *                           additional namespaces to add to the
     *                           namespace list that are not broadcast by
     *                           the server. The namespaces must be UTF-8
     *                           strings.
     *
     * @return array  An array of namespace information with the name as the
     *                key (UTF-8) and the following values:
     * <ul>
     *  <li>delimiter: (string) The namespace delimiter.</li>
     *  <li>hidden: (boolean) Is this a hidden namespace?</li>
     *  <li>name: (string) The namespace name (UTF-8).</li>
     *  <li>
     *   translation: (string) Returns the translated name of the namespace
     *   (UTF-8). Requires RFC 5255 and a previous call to setLanguage().
     *  </li>
     *  <li>
     *   type: (integer) The namespace type. Either:
     *   <ul>
     *    <li>Horde_Imap_Client::NS_PERSONAL</li>
     *    <li>Horde_Imap_Client::NS_OTHER</li>
     *    <li>Horde_Imap_Client::NS_SHARED</li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getNamespaces(array $additional = array())
    {
        $this->login();

        $additional = array_map('strval', $additional);
        $sig = hash('md5', serialize($additional));

        if (isset($this->_init['namespace'][$sig])) {
            return $this->_init['namespace'][$sig];
        }

        $ns = $this->_getNamespaces();

        foreach ($additional as $val) {
            /* Skip namespaces if we have already auto-detected them. Also,
             * hidden namespaces cannot be empty. */
            if (!strlen($val) || isset($ns[$val])) {
                continue;
            }

            $mbox = $this->listMailboxes($val, Horde_Imap_Client::MBOX_ALL, array('delimiter' => true));
            $first = reset($mbox);

            if ($first && ($first['mailbox'] == $val)) {
                $ns[$val] = array(
                    'delimiter' => $first['delimiter'],
                    'hidden' => true,
                    'name' => $val,
                    'translation' => '',
                    'type' => Horde_Imap_Client::NS_SHARED
                );
            }
        }

        if (empty($ns)) {
            /* This accurately determines the namespace information of the
             * base namespace if the NAMESPACE command is not supported.
             * See: RFC 3501 [6.3.8] */
            $mbox = $this->listMailboxes('', Horde_Imap_Client::MBOX_ALL, array('delimiter' => true));
            $first = reset($mbox);
            $ns[''] = array(
                'delimiter' => $first['delimiter'],
                'hidden' => false,
                'name' => '',
                'translation' => '',
                'type' => Horde_Imap_Client::NS_PERSONAL
            );
        }

        $this->_setInit('namespace', array_merge($this->_init['namespace'], array($sig => $ns)));

        return $ns;
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @return array  An array of namespace information. See getNamespaces()
     *                for format.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getNamespaces();

    /**
     * Display if connection to the server has been secured via TLS or SSL.
     *
     * @return boolean  True if the IMAP connection is secured.
     */
    public function isSecureConnection()
    {
        return $this->_isSecure;
    }

    /**
     * Return a list of alerts that MUST be presented to the user (RFC 3501
     * [7.1]).
     *
     * @return array  An array of alert messages.
     */
    abstract public function alerts();

    /**
     * Login to the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function login()
    {
        if ($this->_isAuthenticated) {
            return;
        }

        if ($this->_login()) {
            if (!empty($this->_params['id'])) {
                try {
                    $this->sendID();
                } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
                    // Ignore if server doesn't support ID extension.
                }
            }

            if (!empty($this->_params['comparator'])) {
                try {
                    $this->setComparator();
                } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
                    // Ignore if server doesn't support I18NLEVEL=2
                }
            }
        }

        $this->_isAuthenticated = true;
    }

    /**
     * Login to the IMAP server.
     *
     * @return boolean  Return true if global login tasks should be run.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _login();

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    public function logout()
    {
        if ($this->_isAuthenticated) {
            $this->_logout();
            $this->_isAuthenticated = false;
        }
        $this->_selected = null;
        $this->_mode = 0;
    }

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    abstract protected function _logout();

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info  Overrides the value of the 'id' param and sends
     *                     this information instead.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function sendID($info = null)
    {
        if (!$this->queryCapability('ID')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ID');
        }

        $this->_sendID(is_null($info) ? (empty($this->_params['id']) ? array() : $this->_params['id']) : $info);
    }

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info  The information to send to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _sendID($info);

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function getID()
    {
        if (!$this->queryCapability('ID')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ID');
        }

        return $this->_getID();
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getID();

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $langs  Overrides the value of the 'lang' param and sends
     *                      this list of preferred languages instead. The
     *                      special string 'i-default' can be used to restore
     *                      the language to the server default.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setLanguage($langs = null)
    {
        $lang = null;

        if ($this->queryCapability('LANGUAGE')) {
            $lang = is_null($langs)
                ? (empty($this->_params['lang']) ? null : $this->_params['lang'])
                : $langs;
        }

        return is_null($lang)
            ? null
            : $this->_setLanguage($lang);
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $langs  The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setLanguage($langs);

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getLanguage($list = false)
    {
        if (!$this->queryCapability('LANGUAGE')) {
            return $list ? array() : null;
        }

        return $this->_getLanguage($list);
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getLanguage($list);

    /**
     * Open a mailbox.
     *
     * @param mixed $mailbox  The mailbox to open. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param integer $mode   The access mode. Either
     *   - Horde_Imap_Client::OPEN_READONLY
     *   - Horde_Imap_Client::OPEN_READWRITE
     *   - Horde_Imap_Client::OPEN_AUTO
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function openMailbox($mailbox, $mode = Horde_Imap_Client::OPEN_AUTO)
    {
        $this->login();

        $change = false;
        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox);

        if ($mode == Horde_Imap_Client::OPEN_AUTO) {
            if (is_null($this->_selected) ||
                !$mailbox->equals($this->_selected)) {
                $mode = Horde_Imap_Client::OPEN_READONLY;
                $change = true;
            }
        } else {
            $change = (is_null($this->_selected) ||
                       !$mailbox->equals($this->_selected) ||
                       ($mode != $this->_mode));
        }

        if (!$change) {
            return;
        }

        $this->_openMailbox($mailbox, $mode);
        $this->_selected = $mailbox;
        $this->_mode = $mode;

        if ($this->_initCache(true)) {
            $this->_syncMailbox();
        }
    }

    /**
     * Open a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to open.
     * @param integer $mode                       The access mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                             $mode);

    /**
     * Return the currently opened mailbox and access mode.
     *
     * @return mixed  Null if no mailbox selected, or an array with two
     *                elements:
     *   - mailbox: (Horde_Imap_Client_Mailbox) The mailbox object.
     *   - mode: (integer) Current mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function currentMailbox()
    {
        return is_null($this->_selected)
            ? null
            : array(
                'mailbox' => clone $this->_selected,
                'mode' => $this->_mode
            );
    }

    /**
     * Create a mailbox.
     *
     * @param mixed $mailbox  The mailbox to create. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param array $opts     Additional options:
     *   - special_use: (array) An array of special-use flags to mark the
     *                  mailbox with. The server MUST support RFC 6154.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function createMailbox($mailbox, array $opts = array())
    {
        $this->login();

        if (!$this->queryCapability('CREATE-SPECIAL-USE')) {
            unset($opts['special_use']);
        }

        $this->_createMailbox(Horde_Imap_Client_Mailbox::get($mailbox), $opts);
    }

    /**
     * Create a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to create.
     * @param array $opts                         Additional options. See
     *                                            createMailbox().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                               $opts);

    /**
     * Delete a mailbox.
     *
     * @param mixed $mailbox  The mailbox to delete. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function deleteMailbox($mailbox)
    {
        $this->login();

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox);

        $this->_deleteMailbox($mailbox);
        $this->_deleteMailboxPost($mailbox);
    }

    /**
     * Delete a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to delete.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Actions to perform after a mailbox delete.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The deleted mailbox.
     */
    protected function _deleteMailboxPost(Horde_Imap_Client_Mailbox $mailbox)
    {
        /* Delete mailbox caches. */
        if ($this->_initCache()) {
            $this->_cache->deleteMailbox($mailbox);
        }

        /* Unsubscribe from mailbox. */
        try {
            $this->subscribeMailbox($mailbox, false);
        } catch (Horde_Imap_Client_Exception $e) {
            // Ignore failed unsubscribe request
        }
    }

    /**
     * Rename a mailbox.
     *
     * @param mixed $old  The old mailbox name. Either a
     *                    Horde_Imap_Client_Mailbox object or a string (UTF-8).
     * @param mixed $new  The new mailbox name. Either a
     *                    Horde_Imap_Client_Mailbox object or a string (UTF-8).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function renameMailbox($old, $new)
    {
        // Login will be handled by first listMailboxes() call.

        $old = Horde_Imap_Client_Mailbox::get($old);
        $new = Horde_Imap_Client_Mailbox::get($new);

        /* Check if old mailbox(es) were subscribed to. */
        $base = $this->listMailboxes($old, Horde_Imap_Client::MBOX_SUBSCRIBED, array('delimiter' => true));
        if (empty($base)) {
            $base = $this->listMailboxes($old, Horde_Imap_Client::MBOX_ALL, array('delimiter' => true));
            $base = reset($base);
            $subscribed = array();
        } else {
            $base = reset($base);
            $subscribed = array($base['mailbox']);
        }

        $all_mboxes = array($base['mailbox']);
        if (strlen($base['delimiter'])) {
            $search = $old->list_escape . $base['delimiter'] . '*';
            $all_mboxes = array_merge($all_mboxes, $this->listMailboxes($search, Horde_Imap_Client::MBOX_ALL, array('flat' => true)));
            $subscribed = array_merge($subscribed, $this->listMailboxes($search, Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true)));
        }

        $this->_renameMailbox($old, $new);

        /* Delete mailbox actions. */
        foreach ($all_mboxes as $val) {
            $this->_deleteMailboxPost($val);
        }

        foreach ($subscribed as $val) {
            try {
                $this->subscribeMailbox(new Horde_Imap_Client_Mailbox(substr_replace($val, $new, 0, strlen($old))));
            } catch (Horde_Imap_Client_Exception $e) {
                // Ignore failed subscription requests
            }
        }
    }

    /**
     * Rename a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $old  The old mailbox name.
     * @param Horde_Imap_Client_Mailbox $new  The new mailbox name.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _renameMailbox(Horde_Imap_Client_Mailbox $old,
                                               Horde_Imap_Client_Mailbox $new);

    /**
     * Manage subscription status for a mailbox.
     *
     * @param mixed $mailbox      The mailbox to [un]subscribe to. Either a
     *                            Horde_Imap_Client_Mailbox object or a string
     *                            (UTF-8).
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function subscribeMailbox($mailbox, $subscribe = true)
    {
        $this->login();
        $this->_subscribeMailbox(Horde_Imap_Client_Mailbox::get($mailbox), (bool)$subscribe);
    }

    /**
     * Manage subscription status for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to [un]subscribe
     *                                            to.
     * @param boolean $subscribe                  True to subscribe, false to
     *                                            unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                                  $subscribe);

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param mixed $pattern   The mailbox search pattern(s) (see RFC 3501
     *                         [6.3.8] for the format). A UTF-8 string or an
     *                         array of strings. If a Horde_Imap_Client_Mailbox
     *                         object is given, it is escaped (i.e. wildcard
     *                         patterns are converted to return the miminal
     *                         number of matches possible).
     * @param integer $mode    Which mailboxes to return.  Either:
     *   - Horde_Imap_Client::MBOX_SUBSCRIBED
     *   - Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS
     *   - Horde_Imap_Client::MBOX_UNSUBSCRIBED
     *   - Horde_Imap_Client::MBOX_ALL
     * @param array $options   Additional options:
     * <ul>
     *  <li>
     *   attributes: (boolean) If true, return attribute information under
     *   the 'attributes' key.
     *   DEFAULT: Do not return this information.
     *  </li>
     *  <li>
     *   children: (boolean) Tell server to return children attribute
     *   information (\HasChildren, \HasNoChildren). Requires the
     *   LIST-EXTENDED extension to guarantee this information is returned.
     *   Server MAY return this attribute without this option, or if the
     *   CHILDREN extension is available, but it is not guaranteed.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   delimiter: (boolean) If true, return delimiter information under the
     *   'delimiter' key.
     *   DEFAULT: Do not return this information.
     *  </li>
     *  <li>
     *   flat: (boolean) If true, return a flat list of mailbox names only.
     *   Overrides both the 'attributes' and 'delimiter' options.
     *   DEFAULT: Do not return flat list.
     *  </li>
     *  <li>
     *   recursivematch: (boolean) Force the server to return information
     *   about parent mailboxes that don't match other selection options, but
     *   have some submailboxes that do. Information about children is
     *   returned in the CHILDINFO extended data item ('extended'). Requires
     *   the LIST-EXTENDED extension.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   remote: (boolean) Tell server to return mailboxes that reside on
     *   another server. Requires the LIST-EXTENDED extension.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   special_use: (boolean) Tell server to return special-use attribute
     *   information (\Drafts, \Flagged, \Junk, \Sent, \Trash, \All,
     *   \Archive). Server must support the SPECIAL-USE return option for this
     *   setting to have any effect. Server MAY return this attribute without
     *   this option.
     *   DEFAULT: false
     *  <li>
     *   status: (integer) Tell server to return status information. The
     *   value is a bitmask that may contain any of:
     *   <ul>
     *    <li>Horde_Imap_Client::STATUS_MESSAGES</li>
     *    <li>Horde_Imap_Client::STATUS_RECENT</li>
     *    <li>Horde_Imap_Client::STATUS_UIDNEXT</li>
     *    <li>Horde_Imap_Client::STATUS_UIDVALIDITY</li>
     *    <li>Horde_Imap_Client::STATUS_UNSEEN</li>
     *    <li>Horde_Imap_Client::STATUS_HIGHESTMODSEQ</li>
     *   </ul>
     *   DEFAULT: 0
     *  </li>
     *  <li>
     *   sort: (boolean) If true, return a sorted list of mailboxes?
     *   DEFAULT: Do not sort the list.
     *  </li>
     *  <li>
     *   sort_delimiter: (string) If 'sort' is true, this is the delimiter
     *   used to sort the mailboxes.
     *   DEFAULT: '.'
     *  </li>
     * </ul>
     *
     * @return array  If 'flat' option is true, the array values are a list
     *                of Horde_Imap_Client_Mailbox objects. Otherwise, the
     *                keys are UTF-8 mailbox names and the values are arrays
     *                with these keys:
     *   - attributes: (array) List of lower-cased attributes [only if
     *                 'attributes' option is true].
     *   - delimiter: (string) The delimiter for the mailbox [only if
     *                'delimiter' option is true].
     *   - extended: (TODO) TODO [only if 'recursivematch' option is true and
     *               LIST-EXTENDED extension is supported on the server].
     *   - mailbox: (Horde_Imap_Client_Mailbox) The mailbox object.
     *   - status: (array) See status() [only if 'status' option is true].
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function listMailboxes($pattern,
                                  $mode = Horde_Imap_Client::MBOX_ALL,
                                  array $options = array())
    {
        $this->login();

        $pattern = is_array($pattern)
            ? array_unique($pattern)
            : array($pattern);

        /* Prepare patterns. */
        $plist = array();
        foreach ($pattern as $val) {
            if ($val instanceof Horde_Imap_Client_Mailbox) {
                $val = $val->list_escape;
            }
            $plist[] = preg_replace(
                array("/\*{2,}/", "/\%{2,}/"),
                array('*', '%'),
                Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($val)
            );
        }

        if (isset($options['special_use']) &&
            !$this->queryCapability('SPECIAL-USE')) {
            unset($options['special_use']);
        }

        $ret = $this->_listMailboxes($plist, $mode, $options);

        if (!empty($options['status']) &&
            !$this->queryCapability('LIST-STATUS')) {
            $status = $this->statusMultiple($this->_selected, $options['status']);
            foreach ($status as $key => $val) {
                $ret[$key]['status'] = $val;
            }
        }

        if (empty($options['sort'])) {
            return $ret;
        }

        $list_ob = new Horde_Imap_Client_Mailbox_List(empty($options['flat']) ? array_keys($ret) : $ret);
        $sorted = $list_ob->sort(array(
            'delimiter' => empty($options['sort_delimiter']) ? '.' : $options['sort_delimiter']
        ));

        if (!empty($options['flat'])) {
            return $sorted;
        }

        $out = array();
        foreach ($sorted as $val) {
            $out[$val] = $ret[$val];
        }

        return $out;
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param array $pattern  The mailbox search patterns (UTF7-IMAP strings).
     * @param integer $mode   Which mailboxes to return.
     * @param array $options  Additional options.
     *
     * @return array  See listMailboxes().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listMailboxes($pattern, $mode, $options);

    /**
     * Obtain status information for a mailbox.
     *
     * @param mixed $mailbox  The mailbox to query. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param integer $flags  A bitmask of information requested from the
     *                        server. Allowed flags:
     * <ul>
     *  <li>
     *   Horde_Imap_Client::STATUS_MESSAGES
     *   <ul>
     *    <li>
     *     Return key: messages
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages in the mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_RECENT
     *   <ul>
     *    <li>
     *     Return key: recent
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages with the \Recent
     *     flag set
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDNEXT
     *   <ul>
     *    <li>
     *     Return key: uidnext
     *    </li>
     *    <li>
     *     Return format: (integer) The next UID to be assigned in the
     *     mailbox. Only returned if the server automatically provides the
     *     data.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDNEXT_FORCE
     *   <ul>
     *    <li>
     *     Return key: uidnext
     *    </li>
     *    <li>
     *     Return format: (integer) The next UID to be assigned in the
     *     mailbox. This option will always determine this value, even if the
     *     server does not automatically provide this data.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDVALIDITY
     *   <ul>
     *    <li>
     *     Return key: uidvalidity
     *    </li>
     *    <li>
     *     Return format: (integer) The unique identifier validity of the
     *     mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UNSEEN
     *   <ul>
     *    <li>
     *     Return key: unseen
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages which do not have
     *     the \Seen flag set.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_FIRSTUNSEEN
     *   <ul>
     *    <li>
     *     Return key: firstunseen
     *    </li>
     *    <li>
     *     Return format: (integer) The sequence number of the first unseen
     *     message in the mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_FLAGS
     *   <ul>
     *    <li>
     *     Return key: flags
     *    </li>
     *    <li>
     *     Return format: (array) The list of defined flags in the mailbox
     *     (all flags are in lowercase).
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_PERMFLAGS
     *   <ul>
     *    <li>
     *     Return key: permflags
     *    </li>
     *    <li>
     *     Return format: (array) The list of flags that a client can change
     *     permanently (all flags are in lowercase).
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_HIGHESTMODSEQ
     *   <ul>
     *    <li>
     *     Return key: highestmodseq
     *    </li>
     *    <li>
     *     Return format: (integer) If the server supports the CONDSTORE
     *     IMAP extension, this will be the highest mod-sequence value of all
     *     messages in the mailbox. Else 0 if CONDSTORE not available or the
     *     mailbox does not support mod-sequences.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_SYNCMODSEQ
     *   <ul>
     *    <li>
     *     Return key: syncmodseq
     *    </li>
     *    <li>
     *     Return format: (integer) If caching, and the server supports the
     *     CONDSTORE IMAP extension, this is the cached mod-sequence value of
     *     the mailbox when it was opened for the first time in this access.
     *     Will be null if not caching, CONDSTORE not available, or the
     *     mailbox does not support mod-sequences.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_SYNCFLAGUIDS
     *   <ul>
     *    <li>
     *     Return key: syncflaguids
     *    </li>
     *    <li>
     *     Return format: (Horde_Imap_Client_Ids) If caching, the server
     *     supports the CONDSTORE IMAP extension, and the mailbox contained
     *     cached data when opened for the first time in this access, this is
     *     the list of UIDs in which flags have changed since
     *     STATUS_SYNCMODSEQ.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_SYNCVANISHED
     *   <ul>
     *    <li>
     *     Return key: syncvanished
     *    </li>
     *    <li>
     *     Return format: (Horde_Imap_Client_Ids) If caching, the server
     *     supports the CONDSTORE IMAP extension, and the mailbox contained
     *     cached data when opened for the first time in this access, this is
     *     the list of UIDs which have been deleted since STATUS_SYNCMODSEQ.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDNOTSTICKY
     *   <ul>
     *    <li>
     *     Return key: uidnotsticky
     *    </li>
     *    <li>
     *     Return format: (boolean) If the server supports the UIDPLUS IMAP
     *     extension, and the queried mailbox does not support persistent
     *     UIDs, this value will be true. In all other cases, this value will
     *     be false.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_ALL (DEFAULT)
     *   <ul>
     *    <li>
     *     Shortcut to return 'messages', 'recent', 'uidnext', 'uidvalidity',
     *     and 'unseen' values.
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @return array  An array with the requested keys (see above).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function status($mailbox, $flags = Horde_Imap_Client::STATUS_ALL)
    {
        $this->login();

        $unselected_flags = array(
            'messages' => Horde_Imap_Client::STATUS_MESSAGES,
            'recent' => Horde_Imap_Client::STATUS_RECENT,
            'unseen' => Horde_Imap_Client::STATUS_UNSEEN,
            'uidnext' => Horde_Imap_Client::STATUS_UIDNEXT,
            'uidvalidity' => Horde_Imap_Client::STATUS_UIDVALIDITY
        );

        if ($flags & Horde_Imap_Client::STATUS_ALL) {
            foreach ($unselected_flags as $val) {
                $flags |= $val;
            }
        }

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox);
        $ret = array();
        $smailbox = strval($mailbox);

        /* Catch flags that are not supported. */
        if (($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            $ret['highestmodseq'] = 0;
            $flags &= ~Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }

        if (($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY) &&
            !$this->queryCapability('UIDPLUS')) {
            $ret['uidnotsticky'] = false;
            $flags &= ~Horde_Imap_Client::STATUS_UIDNOTSTICKY;
        }

        /* Handle SYNC related return options. */
        if ($flags & Horde_Imap_Client::STATUS_SYNCMODSEQ) {
            $ret['syncmodseq'] = null;
            $flags &= ~Horde_Imap_Client::STATUS_SYNCMODSEQ;
        }

        if ($flags & Horde_Imap_Client::STATUS_SYNCFLAGUIDS) {
            $ret['syncflaguids'] = $this->getIdsOb();
            $flags &= ~Horde_Imap_Client::STATUS_SYNCFLAGUIDS;
        }

        if ($flags & Horde_Imap_Client::STATUS_SYNCVANISHED) {
            $ret['syncvanished'] = $this->getIdsOb();
            $flags &= ~Horde_Imap_Client::STATUS_SYNCVANISHED;
        }

        /* UIDNEXT return options. */
        if ($flags & Horde_Imap_Client::STATUS_UIDNEXT_FORCE) {
            $flags |= Horde_Imap_Client::STATUS_UIDNEXT;
        }

        if (!$flags) {
            return $ret;
        }

        /* STATUS_PERMFLAGS requires a read/write mailbox. */
        if ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) {
            $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);
        }

        return array_merge($ret, $this->_status($mailbox, $flags));
    }

    /**
     * Obtain status information for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to query.
     * @param integer $flags                      A bitmask of information
     *                                            requested from the server.
     *
     * @return array  See status().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _status(Horde_Imap_Client_Mailbox $mailbox,
                                        $flags);

    /**
     * Perform a STATUS call on multiple mailboxes at the same time.
     *
     * This method leverages the LIST-EXTENDED and LIST-STATUS extensions on
     * the IMAP server to improve the efficiency of this operation.
     *
     * @param array $mailboxes  The mailboxes to query. Either
     *                          Horde_Imap_Client_Mailbox objects, strings
     *                          (UTF-8), or a combination of the two.
     * @param integer $flags    See status().
     * @param array $opts       Additional options:
     *   - sort: (boolean) If true, sort the list of mailboxes?
     *           DEFAULT: Do not sort the list.
     *   - sort_delimiter: (string) If 'sort' is true, this is the delimiter
     *                     used to sort the mailboxes.
     *                     DEFAULT: '.'
     *
     * @return array  An array with the keys as the mailbox names (UTF-8) and
     *                the values as arrays with the requested keys (from the
     *                mask given in $flags).
     */
    public function statusMultiple($mailboxes,
                                   $flags = Horde_Imap_Client::STATUS_ALL,
                                   array $opts = array())
    {
        if (empty($mailboxes)) {
            return array();
        }

        $this->login();

        $opts = array_merge(array(
            'sort' => false,
            'sort_delimiter' => '.'
        ), $opts);

        $need_status = true;
        $ret = array();

        /* Optimization: If there is one mailbox in list, and we are already
         * in that mailbox, we should just do a straight STATUS call. */
        if ($this->queryCapability('LIST-STATUS') &&
            ((count($mailboxes) != 1) ||
            !Horde_Imap_Client_Mailbox::get(reset($mailboxes))->equals($this->_selected))) {
            $status = $to_process = array();
            foreach ($mailboxes as $val) {
                // Force mailboxes containing wildcards to be accessed via
                // STATUS so that wildcards do not return a bunch of mailboxes
                // in the LIST-STATUS response.
                if (strpbrk($val, '*%') === false) {
                    $to_process[] = $val;
                }
                $status[strval($val)] = 1;
            }

            try {
                foreach ($this->listMailboxes($to_process, Horde_Imap_Client::MBOX_ALL, array_merge($opts, array('status' => $flags))) as $val) {
                    if (isset($val['status'])) {
                        $ret[strval($val['mailbox'])] = $val['status'];
                    }
                    unset($status[strval($val['mailbox'])]);
                }
            } catch (Horde_Imap_Client_Exception $e) {}

            if (empty($status)) {
                $need_status = false;
            } else {
                $mailboxes = array_keys($status);
            }
        }

        if ($need_status) {
            foreach ($mailboxes as $val) {
                $val = Horde_Imap_Client_Mailbox::get($val);
                try {
                    $ret[strval($val)] = $this->status($val, $flags);
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        if (!$opts['sort']) {
            return $ret;
        }

        $list_ob = new Horde_Imap_Client_Mailbox_List(array_keys($ret));
        $sorted = $list_ob->sort(array(
            'delimiter' => $opts['sort_delimiter']
        ));

        $out = array();
        foreach ($sorted as $val) {
            $out[$val] = $ret[$val];
        }

        return $out;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param mixed $mailbox  The mailbox to append the message(s) to. Either
     *                        a Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param array $data     The message data to append, along with
     *                        additional options. An array of arrays with
     *                        each embedded array having the following
     *                        entries:
     * <ul>
     *  <li>
     *   data: (mixed) The data to append. If a string or a stream resource,
     *   this will be used as the entire contents of a single message. If an
     *   array, will catenate all given parts into a single message. This
     *   array contains one or more arrays with two keys:
     *   <ul>
     *    <li>
     *     t: (string) Either 'url' or 'text'.
     *    </li>
     *    <li>
     *     v: (mixed) If 't' is 'url', this is the IMAP URL to the message
     *     part to append. If 't' is 'text', this is either a string or
     *     resource representation of the message part data.
     *     DEFAULT: NONE (entry is MANDATORY)
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   flags: (array) An array of flags/keywords to set on the appended
     *   message.
     *   DEFAULT: Only the \Recent flag is set.
     *  </li>
     *  <li>
     *   internaldate: (DateTime) The internaldate to set for the appended
     *   message.
     *   DEFAULT: internaldate will be the same date as when the message was
     *   appended.
     *  </li>
     * </ul>
     * @param array $options  Additonal options:
     *   - create: (boolean) Try to create $mailbox if it does not exist?
     *             DEFAULT: No.
     *
     * @return Horde_Imap_Client_Ids  The UIDs of the appended messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function append($mailbox, $data, array $options = array())
    {
        $this->login();

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox);

        $ret = $this->_append($mailbox, $data, $options);

        if ($ret instanceof Horde_Imap_Client_Ids) {
            return $ret;
        }

        $uids = $this->getIdsOb();

        while (list(,$val) = each($data)) {
            if (is_string($val['data'])) {
                $text = $val['data'];
            } elseif (is_resource($val['data'])) {
                $text = '';
                rewind($val['data']);
                while (!feof($val['data'])) {
                    $text .= fread($val['data'], 512);
                    if (preg_match("/\n\r{2,}/", $text)) {
                        break;
                    }
                }
            }

            $headers = Horde_Mime_Headers::parseHeaders($text);
            $msgid = $headers->getValue('message-id');

            if ($msgid) {
                $search_query = new Horde_Imap_Client_Search_Query();
                $search_query->headerText('Message-ID', $msgid);
                $uidsearch = $this->search($mailbox, $search_query);
                $uids->add($uidsearch['match']);
            }
        }

        return $uids;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to append the
     *                                            message(s) to.
     * @param array $data                         The message data.
     * @param array $options                      Additional options.
     *
     * @return mixed  A Horde_Imap_Client_Ids object containing the UIDs of
     *                the appended messages (if server supports UIDPLUS
     *                extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _append(Horde_Imap_Client_Mailbox $mailbox,
                                        $data, $options);

    /**
     * Request a checkpoint of the currently selected mailbox (RFC 3501
     * [6.4.1]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function check()
    {
        // CHECK only useful if we are already authenticated.
        if ($this->_isAuthenticated) {
            $this->_check();
        }
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _check();

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options  Additional options:
     *   - expunge: (boolean) Expunge all messages flagged as deleted?
     *              DEFAULT: No
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function close(array $options = array())
    {
        // This check catches the non-logged in case.
        if (is_null($this->_selected)) {
            return;
        }

        /* If we are caching, search for deleted messages. */
        if (!empty($options['expunge']) && $this->_initCache(true)) {
            /* Make sure mailbox is read-write to expunge. */
            $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READWRITE);
            if ($this->_mode == Horde_Imap_Client::OPEN_READONLY)  {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Cannot expunge read-only mailbox."),
                    Horde_Imap_Client_Exception::MAILBOX_READONLY
                );
            }

            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag(Horde_Imap_Client::FLAG_DELETED, true);
            $search_res = $this->search($this->_selected, $search_query);
            $mbox = $this->_selected;
        } else {
            $search_res = null;
        }

        $this->_close($options);
        $this->_selected = null;
        $this->_mode = 0;

        if (!is_null($search_res)) {
            $this->_deleteMsgs($mbox, $search_res['match']->ids);
        }
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _close($options);

    /**
     * Expunge deleted messages from the given mailbox.
     *
     * @param mixed $mailbox  The mailbox to expunge. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param array $options  Additional options:
     *   - ids: (Horde_Imap_Client_Ids) A list of messages to expunge, but
     *          only if they are also flagged as deleted.
     *          DEFAULT: All messages marked as deleted will be expunged.
     *   - list: (boolean) If true, returns the list of expunged messages
     *           (UIDs only).
     *           DEFAULT: false
     *
     * @return Horde_Imap_Client_Ids  If 'list' option is true, returns the
     *                                UID list of expunged messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function expunge($mailbox, array $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        /* Don't expunge if the mailbox is readonly. */
        if ($this->_mode == Horde_Imap_Client::OPEN_READONLY) {
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Cannot expunge read-only mailbox."),
                Horde_Imap_Client_Exception::MAILBOX_READONLY
            );
        }

        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return $this->getIdsOb();
        }

        return $this->_expunge($options);
    }

    /**
     * Expunge all deleted messages from the given mailbox.
     *
     * @param array $options  Additional options.
     *
     * @return Horde_Imap_Client_Ids  If 'list' option is true, returns the
     *                                list of expunged messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _expunge($options);

    /**
     * Search a mailbox.
     *
     * @param mixed $mailbox                         The mailbox to search.
     *                                               Either a
     *                                               Horde_Imap_Client_Mailbox
     *                                               object or a string
     *                                               (UTF-8).
     * @param Horde_Imap_Client_Search_Query $query  The search query.
     *                                               Defaults to an ALL
     *                                               search.
     * @param array $options                         Additional options:
     * <ul>
     *  <li>
     *   nocache: (boolean) Don't cache the results.
     *   DEFAULT: false (results cached, if possible)
     *  </li>
     *  <li>
     *   partial: (mixed) The range of results to return (message sequence
     *   numbers).
     *   DEFAULT: All messages are returned.
     *  </li>
     *  <li>
     *   results: (array) The data to return. Consists of zero or more of
     *   the following flags:
     *   <ul>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_COUNT</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MATCH (DEFAULT)</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MAX</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MIN</li>
     *    <li>
     *     Horde_Imap_Client::SEARCH_RESULTS_SAVE (This option is currently
     *     meant for internal use only)
     *    </li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY</li>
     *   </ul>
     *  </li>
     *  <li>
     *   sequence: (boolean) If true, returns an array of sequence numbers.
     *   DEFAULT: Returns an array of UIDs
     *  </li>
     *  <li>
     *   sort: (array) Sort the returned list of messages. Multiple sort
     *   criteria can be specified. Any sort criteria can be sorted in reverse
     *   order (instead of the default ascending order) by adding a
     *   Horde_Imap_Client::SORT_REVERSE element to the array directly before
     *   adding the sort element. The following sort criteria are available:
     *   <ul>
     *    <li>Horde_Imap_Client::SORT_ARRIVAL</li>
     *    <li>Horde_Imap_Client::SORT_CC</li>
     *    <li>Horde_Imap_Client::SORT_DATE</li>
     *    <li>Horde_Imap_Client::SORT_FROM</li>
     *    <li>Horde_Imap_Client::SORT_SEQUENCE</li>
     *    <li>Horde_Imap_Client::SORT_SIZE</li>
     *    <li>Horde_Imap_Client::SORT_SUBJECT</li>
     *    <li>Horde_Imap_Client::SORT_TO</li>
     *    <li>
     *     [On servers that support SORT=DISPLAY, these criteria are also
     *     available:]
     *     <ul>
     *      <li>Horde_Imap_Client::SORT_DISPLAYFROM</li>
     *      <li>Horde_Imap_Client::SORT_DISPLAYTO</li>
     *     </ul>
     *    </li>
     *    <li>
     *     [On servers that support SEARCH=FUZZY, this criteria is also
     *     available:]
     *     <ul>
     *      <li>Horde_Imap_Client::SORT_RELEVANCY</li>
     *     </ul>
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @return array  An array with the following keys:
     *   - count: (integer) The number of messages that match the search
     *            criteria. Always returned.
     *   - match: (Horde_Imap_Client_Ids) The IDs that match $criteria, sorted
     *            if the 'sort' modifier was set. Returned if
     *            Horde_Imap_Client::SEARCH_RESULTS_MATCH is set.
     *   - max: (integer) The UID (default) or message sequence number (if
     *          'sequence' is true) of the highest message that satisifies
     *          $criteria. Returns null if no matches found. Returned if
     *          Horde_Imap_Client::SEARCH_RESULTS_MAX is set.
     *   - min: (integer) The UID (default) or message sequence number (if
     *          'sequence' is true) of the lowest message that satisifies
     *          $criteria. Returns null if no matches found. Returned if
     *          Horde_Imap_Client::SEARCH_RESULTS_MIN is set.
     *   - modseq: (integer) The highest mod-sequence for all messages being
     *            returned. Returned if 'sort' is false, the search query
     *            includes a MODSEQ command, and the server supports the
     *            CONDSTORE IMAP extension.
     *   - relevancy: (array) The list of relevancy scores. Returned if
     *                Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY is set and
     *                the server supports FUZZY search matching.
     *   - save: (boolean) Whether the search results were saved. Returned if
     *           Horde_Imap_Client::SEARCH_RESULTS_SAVE is set.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function search($mailbox, $query = null, array $options = array())
    {
        $this->login();

        if (empty($options['results'])) {
            $options['results'] = array(
                Horde_Imap_Client::SEARCH_RESULTS_MATCH,
                Horde_Imap_Client::SEARCH_RESULTS_COUNT
            );
        }

        // Default to an ALL search.
        if (is_null($query)) {
            $query = new Horde_Imap_Client_Search_Query();
        }

        // Check for supported charset.
        $options['_query'] = $query->build($this->capability());
        if (!is_null($options['_query']['charset']) &&
            array_key_exists($options['_query']['charset'], $this->_init['s_charset']) &&
            !$this->_init['s_charset'][$options['_query']['charset']]) {
            foreach (array_merge(array_keys(array_filter($this->_init['s_charset'])), array('US-ASCII')) as $val) {
                try {
                    $new_query = clone $query;
                    $new_query->charset($val);
                    break;
                } catch (Horde_Imap_Client_Exception_SearchCharset $e) {
                    unset($new_query);
                }
            }

            if (!isset($new_query)) {
                throw $e;
            }

            $query = $new_query;
            $options['_query'] = $query->build($this->capability());
        }

        /* RFC 6203: MUST NOT request relevancy results if we are not using
         * FUZZY searching. */
        if (in_array(Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY, $options['results']) &&
            !in_array('SEARCH=FUZZY', $options['_query']['exts_used'])) {
            throw new InvalidArgumentException('Cannot specify RELEVANCY results if not doing a FUZZY search.');
        }

        /* Optimization - if query is just for a count of either RECENT or
         * ALL messages, we can send status information instead. Can't
         * optimize with unseen queries because we may cause an infinite loop
         * between here and the status() call. */
        if ((count($options['results']) == 1) &&
            (reset($options['results']) == Horde_Imap_Client::SEARCH_RESULTS_COUNT)) {
            switch ($options['_query']['query']) {
            case 'ALL':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
                return array('count' => $ret['messages']);

            case 'RECENT':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_RECENT);
                return array('count' => $ret['recent']);
            }
        }

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        /* Take advantage of search result caching.  If CONDSTORE available,
         * we can cache all queries and invalidate the cache when the MODSEQ
         * changes. If CONDSTORE not available, we can only store queries
         * that don't involve flags. We store results by hashing the options
         * array - the generated query is already added to '_query' key
         * above. */
        $cache = null;
        if (empty($options['nocache']) &&
            $this->_initCache(true) &&
            (isset($this->_init['enabled']['CONDSTORE']) ||
             !$query->flagSearch())) {
            $cache = $this->_getSearchCache('search', $this->_selected, $options);
            if (is_array($cache)) {
                if (isset($cache['data']['match'])) {
                    $cache['data']['match'] = $this->getIdsOb($cache['data']['match']);
                }
                return $cache['data'];
            }
        }

        /* Optimization: Catch when there are no messages in a mailbox. */
        $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_HIGHESTMODSEQ);
        if ($status_res['messages'] ||
            in_array(Horde_Imap_Client::SEARCH_RESULTS_SAVE, $options['results'])) {
            $ret = $this->_search($query, $options);
        } else {
            $ret = array(
                'count' => 0
            );

            foreach ($options['results'] as $val) {
                switch ($val) {
                case Horde_Imap_Client::SEARCH_RESULTS_MATCH:
                    $ret['match'] = $this->getIdsOb();
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MAX:
                    $ret['max'] = null;
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                    $ret['min'] = null;
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                    if (isset($status_res['highestmodseq'])) {
                        $ret['modseq'] = $status_res['highestmodseq'];
                    }
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY:
                    $ret['relevancy'] = array();
                    break;
                }
            }
        }

        if ($cache) {
            $save = $ret;
            if (isset($save['match'])) {
                $save['match'] = strval($ret['match']);
            }
            $this->_setSearchCache($save, $cache);
        }

        return $ret;
    }

    /**
     * Search a mailbox.
     *
     * @param object $query   The search query.
     * @param array $options  Additional options. The '_query' key contains
     *                        the value of $query->build().
     *
     * @return Horde_Imap_Client_Ids  An array of IDs.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _search($query, $options);

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function setComparator($comparator = null)
    {
        $comp = is_null($comparator)
            ? (empty($this->_params['comparator']) ? null : $this->_params['comparator'])
            : $comparator;
        if (is_null($comp)) {
            return;
        }

        $this->login();

        $i18n = $this->queryCapability('I18NLEVEL');
        if (empty($i18n) || (max($i18n) < 2)) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension(
                'I18NLEVEL',
                'The IMAP server does not support changing SEARCH/SORT comparators.'
            );
        }

        $this->_setComparator($comp);
    }

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setComparator($comparator);

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getComparator()
    {
        $this->login();

        $i18n = $this->queryCapability('I18NLEVEL');
        if (empty($i18n) || (max($i18n) < 2)) {
            return null;
        }

        return $this->_getComparator();
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getComparator();

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param mixed $mailbox  The mailbox to query. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param array $options  Additional options:
     * <ul>
     *  <li>
     *   criteria: (mixed) The following thread criteria are available:
     *   <ul>
     *    <li>Horde_Imap_Client::THREAD_ORDEREDSUBJECT</li>
     *    <li>Horde_Imap_Client::THREAD_REFERENCES</li>
     *    <li>Horde_Imap_Client::THREAD_REFS</li>
     *    <li>
     *     Other algorithms can be explicitly specified by passing the IMAP
     *     thread algorithm in as a string value.
     *    </li>
     *   </ul>
     *   DEFAULT: Horde_Imap_Client::THREAD_ORDEREDSUBJECT
     *  </li>
     *  <li>
     *   search: (Horde_Imap_Client_Search_Query) The search query.
     *   DEFAULT: All messages in mailbox included in thread sort.
     *  </li>
     *  <li>
     *   sequence: (boolean) If true, each message is stored and referred to
     *   by its message sequence number.
     *   DEFAULT: Stored/referred to by UID.
     *  </li>
     * </ul>
     *
     * @return Horde_Imap_Client_Data_Thread  A thread data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function thread($mailbox, array $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        /* Take advantage of search result caching.  If CONDSTORE available,
         * we can cache all queries and invalidate the cache when the MODSEQ
         * changes. If CONDSTORE not available, we can only store queries
         * that don't involve flags. See search() for similar caching. */
        $cache = null;
        if ($this->_initCache(true) &&
            (isset($this->_init['enabled']['CONDSTORE']) ||
             empty($options['search']) ||
             !$options['search']->flagSearch())) {
            $cache = $this->_getSearchCache('thread', $this->_selected, $options);
            if (is_array($cache)) {
                if ($cache['data'] instanceof Horde_Imap_Client_Data_Thread) {
                    return $cache['data'];
                }
                $cache = $cache['id'];
            }
        }

        $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);

        $ob = $status_res['messages']
            ? $this->_thread($options)
            : new Horde_Imap_Client_Data_Thread(array(), empty($options['sequence']) ? 'uid' : 'sequence');

        if ($cache) {
            $this->_setSearchCache($ob, $cache);
        }

        return $ob;
    }

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param array $options  Additional options. See thread().
     *
     * @return Horde_Imap_Client_Data_Thread  A thread data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _thread($options);

    /**
     * Fetch message data (see RFC 3501 [6.4.5]).
     *
     * @param mixed $mailbox                        The mailbox to search.
     *                                              Either a
     *                                              Horde_Imap_Client_Mailbox
     *                                              object or a string (UTF-8).
     * @param Horde_Imap_Client_Fetch_Query $query  Fetch query object.
     * @param array $options                        Additional options:
     *   - changedsince: (integer) Only return messages that have a
     *                   mod-sequence larger than this value. This option
     *                   requires the CONDSTORE IMAP extension (if not present,
     *                   this value is ignored). Additionally, the mailbox
     *                   must support mod-sequences or an exception will be
     *                   thrown. If valid, this option implicity adds the
     *                   mod-sequence fetch criteria to the fetch command.
     *                   DEFAULT: Mod-sequence values are ignored.
     *   - ids: (Horde_Imap_Client_Ids) A list of messages to fetch data from.
     *          DEFAULT: All messages in $mailbox will be fetched.
     *
     * @return Horde_Imap_Client_Fetch_Results  A results object.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function fetch($mailbox, $query, array $options = array())
    {
        $this->login();

        $query = clone $query;

        $cache_array = $header_cache = $new_query = array();
        $res_seq = null;

        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return new Horde_Imap_Client_Fetch_Results($this->_fetchDataClass);
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            /* SEARCHRES requires server support. */
            throw new Horde_Imap_Client_Exception_NoSupportExtension('SEARCHRES');
        }

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        $cf = $this->_initCache(true)
            ? $this->_cacheFields()
            : array();

        if (!empty($cf)) {
            /* If using cache, we store by UID so we need to return UIDs. */
            $query->uid();
        }

        if ($query->contains(Horde_Imap_Client::FETCH_MODSEQ) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            unset($query[$k]);
        }

        /* Determine if caching is available and if anything in $query is
         * cacheable.
         * TODO: Re-add base headertext caching. */
        foreach ($cf as $k => $v) {
            if (isset($query[$k])) {
                switch ($k) {
                case Horde_Imap_Client::FETCH_ENVELOPE:
                case Horde_Imap_Client::FETCH_FLAGS:
                case Horde_Imap_Client::FETCH_IMAPDATE:
                case Horde_Imap_Client::FETCH_SIZE:
                case Horde_Imap_Client::FETCH_STRUCTURE:
                    $cache_array[$k] = $v;
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    $this->_temp['headers_caching'] = array();

                    foreach ($query[$k] as $key => $val) {
                        /* Only cache if directly requested.  Iterate through
                         * requests to ensure at least one can be cached. */
                        if (!empty($val['cache']) && !empty($val['peek'])) {
                            $cache_array[$k] = $v;
                            ksort($val);
                            $header_cache[$key] = hash('md5', serialize($val));
                        }
                    }
                    break;
                }
            }
        }

        $ret = new Horde_Imap_Client_Fetch_Results(
            $this->_fetchDataClass,
            $options['ids']->sequence ? Horde_Imap_Client_Fetch_Results::SEQUENCE : Horde_Imap_Client_Fetch_Results::UID
        );

        /* If nothing is cacheable, we can do a straight search. */
        if (empty($cache_array)) {
            $this->_fetch($ret, $query, $options);
            return $ret;
        }

        /* If doing a changedsince search, limit the UIDs now. */
        if (!empty($options['changedsince'])) {
            $changed_query = new Horde_Imap_Client_Fetch_Query();
            if ($options['ids']->sequence) {
                $changed_query->seq();
            } else {
                $changed_query->uid();
            }

            $this->_fetch($ret, $changed_query, array(
                'changedsince' => $options['changedsince'],
                'ids' => $options['ids']
            ));
            if (!count($ret)) {
                return $ret;
            }

            $options['ids'] = $this->getIdsOb($ret->ids(), $options['ids']->sequence);
        }

        /* Grab Seq -> UID lookup. */
        $res_seq = $this->_getSeqUidLookup($options['ids']);
        $ids = $options['ids']->sequence
            ? array_keys($res_seq['lookup'])
            : $res_seq['uids'];

        /* Get the cached values. */
        $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_UIDVALIDITY);
        $data = $this->_cache->get($this->_selected, $res_seq['uids']->ids, array_values($cache_array), $status_res['uidvalidity']);

        /* Build a list of what we still need. */
        foreach ($ids as $val) {
            $crit = clone $query;

            if ($options['ids']->sequence) {
                $uid = $res_seq['lookup'][$val];
                unset($crit[Horde_Imap_Client::FETCH_SEQ]);
            } else {
                $uid = $val;
            }

            /* UID will be added into the results object below. */
            unset($crit[Horde_Imap_Client::FETCH_UID]);

            foreach ($cache_array as $key => $cid) {
                switch ($key) {
                case Horde_Imap_Client::FETCH_ENVELOPE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Imap_Client_Data_Envelope)) {
                        $ret->get($val)->setEnvelope($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_FLAGS:
                    if (isset($data[$uid][$cid]) &&
                        is_array($data[$uid][$cid])) {
                        $ret->get($val)->setFlags($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    /* HEADERS caching. */
                    foreach ($header_cache as $hkey => $hval) {
                        if (isset($data[$uid][$cid][$hval])) {
                            /* We have found a cached entry with the same MD5
                             * sum. */
                            $ret->get($val)->setHeaders($hkey, $data[$uid][$cid][$hval]);
                            $crit->remove($key, $hkey);
                        } else {
                            $this->_temp['headers_caching'][$hkey] = $hval;
                        }
                    }
                    break;

                case Horde_Imap_Client::FETCH_IMAPDATE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Imap_Client_DateTime)) {
                        $ret->get($val)->setImapDate($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_SIZE:
                    if (isset($data[$uid][$cid])) {
                        $ret->get($val)->setSize($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_STRUCTURE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Mime_Part)) {
                        $ret->get($val)->setStructure($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;
                }
            }

            if (count($crit)) {
                $sig = $crit->hash();
                if (isset($new_query[$sig])) {
                    $new_query[$sig]['i']->add($val);
                } else {
                    $new_query[$sig] = array(
                        'c' => $crit,
                        'i' => $this->getIdsOb($val, $options['ids']->sequence)
                    );
                }
            }
        }

        foreach ($new_query as $val) {
            $this->_fetch($ret, $val['c'], array_merge($options, array(
                'ids' => $val['i']
            )));
        }

        foreach ($ret as $key => $val) {
            if ($options['ids']->sequence) {
                $val->setSeq($key);
                $val->setUid($res_seq['lookup'][$key]);
            } else {
                $val->setUid($key);
            }
        }

        return $ret;
    }

    /**
     * Fetch message data.
     *
     * @param Horde_Imap_Client_Fetch_Results $results  Fetch results.
     * @param Horde_Imap_Client_Fetch_Query $query      Fetch query object.
     * @param array $options                            Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _fetch(Horde_Imap_Client_Fetch_Results $results,
                                       Horde_Imap_Client_Fetch_Query $query,
                                       $options);

    /**
     * Get the list of vanished messages (UIDs that have been expunged since a
     * given mod-sequence value).
     *
     * This method requires the QRESYNC IMAP extension.
     *
     * @param mixed $mailbox   The mailbox to query. Either a
     *                         Horde_Imap_Client_Mailbox object or a string
     *                         (UTF-8).
     * @param integer $modseq  Search for expunged messages after this
     *                         mod-sequence value.
     * @param array $opts      Additional options:
     *   - ids: (Horde_Imap_Client_Ids)  Restrict to these UIDs.
     *          DEFAULT: Returns full list of UIDs.
     *
     * @return Horde_Imap_Client_Ids  List of UIDs that have vanished.
     *
     * @throws Horde_Imap_Client_NoSupportExtension
     */
    public function vanished($mailbox, $modseq, array $opts = array())
    {
        $this->login();

        /* Requires QRESYNC and UIDs. */
        if (!isset($this->_init['enabled']['QRESYNC'])) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('QRESYNC');
        }

        if (empty($opts['ids'])) {
            $opts['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($opts['ids']->isEmpty()) {
            return $this->getIdsOb();
        } elseif ($opts['ids']->sequence) {
            throw new InvalidArgumentException('Vanished requires UIDs.');
        }

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        return $this->_vanished($modseq, $opts['ids']);
    }

    /**
     * Get the list of vanished messages.
     *
     * @param integer $modseq             Mod-sequence value.
     * @param Horde_Imap_Client_Ids $ids  UIDs.
     *
     * @return Horde_Imap_Client_Ids  List of UIDs that have vanished.
     */
    abstract protected function _vanished($modseq, Horde_Imap_Client_Ids $ids);

    /**
     * Store message flag data (see RFC 3501 [6.4.6]).
     *
     * @param mixed $mailbox  The mailbox containing the messages to modify.
     *                        Either a Horde_Imap_Client_Mailbox object or a
     *                        string (UTF-8).
     * @param array $options  Additional options:
     *   - add: (array) An array of flags to add.
     *          DEFAULT: No flags added.
     *   - ids: (Horde_Imap_Client_Ids) The list of messages to modify.
     *          DEFAULT: All messages in $mailbox will be modified.
     *   - remove: (array) An array of flags to remove.
     *             DEFAULT: No flags removed.
     *   - replace: (array) Replace the current flags with this set
     *              of flags. Overrides both the 'add' and 'remove' options.
     *              DEFAULT: No replace is performed.
     *   - unchangedsince: (integer) Only changes flags if the mod-sequence ID
     *                     of the message is equal or less than this value.
     *                     Requires the CONDSTORE IMAP extension on the server.
     *                     Also requires the mailbox to support mod-sequences.
     *                     Will throw an exception if either condition is not
     *                     met.
     *                     DEFAULT: mod-sequence is ignored when applying
     *                              changes
     *
     * @return Horde_Imap_Client_Ids  A Horde_Imap_Client_Ids object
     *                                containing the list of IDs that failed
     *                                the 'unchangedsince' test.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function store($mailbox, array $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        /* SEARCHRES requires server support. */
        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return $this->getIdsOb();
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('SEARCHRES');
        }

        if (!empty($options['unchangedsince']) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('CONDSTORE');
        }

        return $this->_store($options);
    }

    /**
     * Store message flag data.
     *
     * @param array $options  Additional options.
     *
     * @return Horde_Imap_Client_Ids  A Horde_Imap_Client_Ids object
     *                                containing the list of IDs that failed
     *                                the 'unchangedsince' test.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _store($options);

    /**
     * Copy messages to another mailbox.
     *
     * @param mixed $source   The source mailbox. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param mixed $dest     The destination mailbox. Either a
     *                        Horde_Imap_Client_Mailbox object or a string
     *                        (UTF-8).
     * @param array $options  Additional options:
     *   - create: (boolean) Try to create $dest if it does not exist?
     *             DEFAULT: No.
     *   - ids: (Horde_Imap_Client_Ids) The list of messages to copy.
     *          DEFAULT: All messages in $mailbox will be copied.
     *   - move: (boolean) If true, delete the original messages.
     *           DEFAULT: Original messages are not deleted.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function copy($source, $dest, array $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($source, empty($options['move']) ? Horde_Imap_Client::OPEN_AUTO : Horde_Imap_Client::OPEN_READWRITE);

        /* SEARCHRES requires server support. */
        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return array();
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('SEARCHRES');
        }

        return $this->_copy(Horde_Imap_Client_Mailbox::get($dest), $options);
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $dest  The destination mailbox.
     * @param array $options                   Additional options.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _copy(Horde_Imap_Client_Mailbox $dest,
                                      $options);

    /**
     * Set quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param mixed $root       The quota root. Either a
     *                          Horde_Imap_Client_Mailbox object or a string
     *                          (UTF-8).
     * @param array $resources  The resource values to set. Keys are the
     *                          resource atom name; value is the resource
     *                          value.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function setQuota($root, array $resources = array())
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('QUOTA');
        }

        if (!empty($resources)) {
            $this->_setQuota(Horde_Imap_Client_Mailbox::get($root), $resources);
        }
    }

    /**
     * Set quota limits.
     *
     * @param Horde_Imap_Client_Mailbox $root  The quota root.
     * @param array $resources                 The resource values to set.
     *
     * @return boolean  True on success.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setQuota(Horde_Imap_Client_Mailbox $root,
                                          $resources);

    /**
     * Get quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param mixed $root  The quota root. Either a Horde_Imap_Client_Mailbox
     *                     object or a string (UTF-8).
     *
     * @return mixed  An array with resource keys. Each key holds an array
     *                with 2 values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function getQuota($root)
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('QUOTA');
        }

        return $this->_getQuota(Horde_Imap_Client_Mailbox::get($root));
    }

    /**
     * Get quota limits.
     *
     * @param Horde_Imap_Client_Mailbox $root  The quota root.
     *
     * @return mixed  An array with resource keys. Each key holds an array
     *                with 2 values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuota(Horde_Imap_Client_Mailbox $root);

    /**
     * Get quota limits for a mailbox. The server must support the IMAP QUOTA
     * extension (RFC 2087).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with resource keys: each of these keys
     *                holds an array with 2 values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function getQuotaRoot($mailbox)
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('QUOTA');
        }

        return $this->_getQuotaRoot(Horde_Imap_Client_Mailbox::get($mailbox));
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with resource keys: each of these keys
     *                holds an array with 2 values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Get the ACL rights for a given mailbox. The server must support the
     * IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8).
     *
     * @return array  An array with identifiers as the keys and
     *                Horde_Imap_Client_Data_Acl objects as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getACL($mailbox)
    {
        $this->login();
        return $this->_getACL(Horde_Imap_Client_Mailbox::get($mailbox));
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return array  An array with identifiers as the keys and
     *                Horde_Imap_Client_Data_Acl objects as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getACL(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param mixed $mailbox      A mailbox. Either a Horde_Imap_Client_Mailbox
     *                            object or a string (UTF-8).
     * @param string $identifier  The identifier to alter (UTF-8).
     * @param array $options      Additional options:
     *   - rights: (string) The rights to alter or set.
     *   - action: (string, optional) If 'add' or 'remove', adds or removes the
     *             specified rights. Sets the rights otherwise.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function setACL($mailbox, $identifier, $options)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ACL');
        }

        if (empty($options['rights'])) {
            if (!isset($options['action']) ||
                ($options['action'] != 'add' && $options['action'] != 'remove')) {
                $this->_deleteACL(
                    Horde_Imap_Client_Mailbox::get($mailbox),
                    Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier)
                );
            }
            return;
        }

        $acl = ($options['rights'] instanceof Horde_Imap_Client_Data_Acl)
            ? $options['rights']
            : new Horde_Imap_Client_Data_Acl(strval($options['rights']));

        $options['rights'] = $acl->getString(
            $this->queryCapability('RIGHTS')
                ? Horde_Imap_Client_Data_AclCommon::RFC_4314
                : Horde_Imap_Client_Data_AclCommon::RFC_2086
        );
        if (isset($options['action'])) {
            switch ($options['action']) {
            case 'add':
                $options['rights'] = '+' . $options['rights'];
                break;
            case 'remove':
                $options['rights'] = '-' . $options['rights'];
                break;
            }
        }

        $this->_setACL(
            Horde_Imap_Client_Mailbox::get($mailbox),
            Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier),
            $options
        );
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param string $identifier                  The identifier to alter
     *                                            (UTF7-IMAP).
     * @param array $options                      Additional options. 'rights'
     *                                            contains the string of
     *                                            rights to set on the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setACL(Horde_Imap_Client_Mailbox $mailbox,
                                        $identifier, $options);

    /**
     * Deletes ACL rights for a given mailbox/identifier.
     *
     * @param mixed $mailbox      A mailbox. Either a Horde_Imap_Client_Mailbox
     *                            object or a string (UTF-8).
     * @param string $identifier  The identifier to delete (UTF-8).
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function deleteACL($mailbox, $identifier)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ACL');
        }

        $this->_deleteACL(
            Horde_Imap_Client_Mailbox::get($mailbox),
            Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier)
        );
    }

    /**
     * Deletes ACL rights for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param string $identifier                  The identifier to delete
     *                                            (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _deleteACL(Horde_Imap_Client_Mailbox $mailbox,
                                           $identifier);

    /**
     * List the ACL rights for a given mailbox/identifier. The server must
     * support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox      A mailbox. Either a Horde_Imap_Client_Mailbox
     *                            object or a string (UTF-8).
     * @param string $identifier  The identifier to query (UTF-8).
     *
     * @return Horde_Imap_Client_Data_AclRights  An ACL data rights object.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function listACLRights($mailbox, $identifier)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ACL');
        }

        return $this->_listACLRights(
            Horde_Imap_Client_Mailbox::get($mailbox),
            Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier)
        );
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param string $identifier                  The identifier to query
     *                                            (UTF7-IMAP).
     *
     * @return Horde_Imap_Client_Data_AclRights  An ACL data rights object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                               $identifier);

    /**
     * Get the ACL rights for the current user for a given mailbox. The
     * server must support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8).
     *
     * @return Horde_Imap_Client_Data_Acl  An ACL data object.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function getMyACLRights($mailbox)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('ACL');
        }

        return $this->_getMyACLRights(Horde_Imap_Client_Mailbox::get($mailbox));
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return Horde_Imap_Client_Data_Acl  An ACL data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Return master list of ACL rights available on the server.
     *
     * @return array  A list of ACL rights.
     */
    public function allAclRights()
    {
        $this->login();

        $rights = array(
            Horde_Imap_Client::ACL_LOOKUP,
            Horde_Imap_Client::ACL_READ,
            Horde_Imap_Client::ACL_SEEN,
            Horde_Imap_Client::ACL_WRITE,
            Horde_Imap_Client::ACL_INSERT,
            Horde_Imap_Client::ACL_POST,
            Horde_Imap_Client::ACL_ADMINISTER
        );

        if ($capability = $this->queryCapability('RIGHTS')) {
            // Add rights defined in CAPABILITY string (RFC 4314).
            return array_merge($rights, str_split(reset($capability)));
        }

        // Add RFC 2086 rights (deprecated by RFC 4314, but need to keep for
        // compatibility with old servers).
        return array_merge($rights, array(
            Horde_Imap_Client::ACL_CREATE,
            Horde_Imap_Client::ACL_DELETE
        ));
    }

    /**
     * Get metadata for a given mailbox. The server must support either the
     * IMAP METADATA extension (RFC 5464) or the ANNOTATEMORE extension
     * (http://ietfreport.isoc.org/idref/draft-daboo-imap-annotatemore/).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8).
     * @param array $entries  The entries to fetch (UTF-8 strings).
     * @param array $options  Additional options:
     *   - depth: (string) Either "0", "1" or "infinity". Returns only the
     *            given value (0), only values one level below the specified
     *            value (1) or all entries below the specified value
     *            (infinity).
     *   - maxsize: (integer) The maximal size the returned values may have.
     *              DEFAULT: No maximal size.
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values. If 'maxsize' is set, and entries
     *                exist on the server larger than this size, the size will
     *                be returned in the key '*longentries'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getMetadata($mailbox, $entries, array $options = array())
    {
        $this->login();

        if (!is_array($entries)) {
            $entries = array($entries);
        }

        return $this->_getMetadata(Horde_Imap_Client_Mailbox::get($mailbox), array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $entries), $options);
    }

    /**
     * Get metadata for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param array $entries                      The entries to fetch
     *                                            (UTF7-IMAP strings).
     * @param array $options                      Additional options.
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                             $entries, $options);

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8). If empty, sets a
     *                        server annotation.
     * @param array $data     A set of data values. The metadata values
     *                        corresponding to the keys of the array will
     *                        be set to the values in the array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setMetadata($mailbox, $data)
    {
        $this->login();
        $this->_setMetadata(Horde_Imap_Client_Mailbox::get($mailbox), $data);
    }

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param array $data                         A set of data values. See
     *                                            setMetadata() for format.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                             $data);

    /* Public utility functions. */

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object or a string (UTF-8).
     * @param array $addl     Additional cache info to add to the cache ID
     *                        string.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of the mailbox changes. The uidvalidity
     *                 will always be the first element, and will be delimited
     *                 by the '|' character.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getCacheId($mailbox, array $addl = array())
    {
        $query = Horde_Imap_Client::STATUS_UIDVALIDITY | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UIDNEXT;

        /* Use MODSEQ as cache ID if CONDSTORE extension is available. */
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $query |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        } else {
            $query |= Horde_Imap_Client::STATUS_UIDNEXT_FORCE;
        }

        $status = $this->status($mailbox, $query);

        if (empty($status['highestmodseq'])) {
            $parts = array(
                'V' . $status['uidvalidity'],
                'U' . $status['uidnext'],
                'M' . $status['messages']
            );
        } else {
            $parts = array(
                'V' . $status['uidvalidity'],
                'H' . $status['highestmodseq']
            );
        }

        return implode('|', array_merge($parts, $addl));
    }

    /**
     * Parses a cacheID created by getCacheId().
     *
     * @param string $id  The cache ID.
     *
     * @return array  An array with the following information:
     *   - highestmodseq: (integer)
     *   - messages: (integer)
     *   - uidnext: (integer)
     *   - uidvalidity: (integer) Always present
     */
    public function parseCacheId($id)
    {
        $data = array(
            'H' => 'highestmodseq',
            'M' => 'messages',
            'U' => 'uidnext',
            'V' => 'uidvalidity'
        );
        $info = array();

        foreach (explode('|', $id) as $part) {
            if (isset($data[$part[0]])) {
                $info[$data[$part[0]]] = intval(substr($part, 1));
            }
        }

        return $info;
    }

    /**
     * Determines if the given charset is valid for search-related queries.
     * This check pertains just to the basic IMAP SEARCH command.
     *
     * @param string $charset  The query charset.
     *
     * @return boolean  True if server supports this charset.
     */
    public function validSearchCharset($charset)
    {
        $charset = strtoupper($charset);

        if ($charset == 'US-ASCII') {
            return true;
        }

        if (!isset($this->_init['s_charset'][$charset])) {
            $s_charset = $this->_init['s_charset'];

            /* Use a dummy search query and search for BADCHARSET response. */
            $query = new Horde_Imap_Client_Search_Query();
            $query->charset($charset, false);
            $query->ids($this->getIdsOb(1, true));
            $query->text('a');
            try {
                $this->search('INBOX', $query, array(
                    'nocache' => true,
                    'sequence' => true
                ));
                $s_charset[$charset] = true;
            } catch (Horde_Imap_Client_Exception $e) {
                $s_charset[$charset] = ($e->getCode() != Horde_Imap_Client_Exception::BADCHARSET);
            }

            $this->_setInit('s_charset', $s_charset);
        }

        return $this->_init['s_charset'][$charset];
    }

    /**
     * Output debug information.
     *
     * @param string $msg    Debug line.
     * @param string $type   The message type. One of the following:
     *   - Horde_Imap_Client::DEBUG_RAW: None (output raw message)
     *   - Horde_Imap_Client::DEBUG_CLIENT: Client command
     *   - Horde_Imap_Client::DEBUG_INFO: Informational message
     *   - Horde_Imap_Client::DEBUG_SERVER: Server command
     */
    public function writeDebug($msg, $type = Horde_Imap_Client::DEBUG_RAW)
    {
        if (!$this->_debug) {
            return;
        }

        $pre = '';

        if ($type) {
            $new_time = microtime(true);
            if (isset($this->_temp['debug_time'])) {
                if (($diff = ($new_time - $this->_temp['debug_time'])) > Horde_Imap_Client::SLOW_COMMAND) {
                    fwrite($this->_debug, '>> Slow IMAP Command: ' . round($diff, 3) . " seconds\n");
                }
            } else {
                fwrite(
                    $this->_debug,
                    str_repeat('-', 30) . "\n" . '>> Timestamp: ' . date('r') . "\n"
                );
            }

            $this->_temp['debug_time'] = $new_time;

            switch ($type) {
            case Horde_Imap_Client::DEBUG_CLIENT:
                $pre .= 'C: ';
                break;

            case Horde_Imap_Client::DEBUG_INFO:
                $pre .= '>> ';
                break;

            case Horde_Imap_Client::DEBUG_SERVER:
                $pre .= 'S: ';
                break;
            }
        } elseif (isset($this->_temp['debug_buffer'])) {
            $pre = $this->_temp['debug_buffer'];
        }

        if (substr($msg, -1) == "\n") {
            fwrite($this->_debug, $pre . $msg);
            unset($this->_temp['debug_buffer']);
        } else {
            $this->_temp['debug_buffer'] = $pre . $msg;
        }
    }

    /* Private utility functions. */

    /**
     * Returns UIDs for an ALL search, or for a sequence number -> UID lookup.
     *
     * @param Horde_Imap_Client_Ids $ids  The IDs to lookup.
     * @param boolean $reverse            Perform reverse lookup (UID ->
     *                                    Sequence number) if needed.
     *
     * @return array  An array with 2 possible entries:
     *   - lookup: (array) The mapping of sequence numbers [keys] to UIDs
     *             [values]. Calculated if $reverse is true or $ids are
     *             sequence numbers.
     *   - uids: (Horde_Imap_Client_Ids) The list of UIDs.
     */
    protected function _getSeqUidLookup(Horde_Imap_Client_Ids $ids,
                                        $reverse = false)
    {
        $ret = array('lookup' => array());

        if (count($ids) && !$ids->sequence && !$reverse) {
            $ret['uids'] = $ids;
            return $ret;
        }

        if ($ids->all || $ids->search_res) {
            $search = null;
        } else {
            $search = new Horde_Imap_Client_Search_Query();
            $search->ids($ids);
        }

        $res = $this->search($this->_selected, $search, array(
            'sequence' => !$ids->all && !$ids->sequence,
            'sort' => array(Horde_Imap_Client::SORT_SEQUENCE)
        ));

        if ($res['count']) {
            $ret['uids'] = ($ids->all || $ids->sequence)
                ? $res['match']
                : $ids;

            if ($ids->all) {
                $seq = range(1, $res['count']);
            } else {
                $seq = $ids->sequence
                    ? $ids->ids
                    : $res['match']->ids;
                sort($seq, SORT_NUMERIC);
            }
            $ret['lookup'] = array_combine($seq, $ret['uids']->ids);
        }

        return $ret;
    }

    /**
     * Store FETCH data in cache.
     *
     * @param Horde_Imap_Client_Fetch_Results $data  The fetch results.
     * @param array $options                         Additional options:
     *   - fields: (array) Only update these cache fields.
     *             DEFAULT: Update all cache fields.
     *   - mailbox: (Horde_Imap_Client_Mailbox) The mailbox to update.
     *              DEFAULT: The selected mailbox.
     *   - uidvalid: (integer) The UID validity number.
     *               DEFAULT: UIDVALIDITY discovered via a status() call.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _updateCache(Horde_Imap_Client_Fetch_Results $data,
                                    array $options = array())
    {
        $mailbox = empty($options['mailbox'])
            ? $this->_selected
            : $options['mailbox'];

        if (!$this->_initCache(empty($options['mailbox']))) {
            return;
        }

        if (in_array(strval($mailbox), $this->_params['cache']['fetch_ignore'])) {
            $this->writeDebug(sprintf("IGNORING cached FETCH data (mailbox: %s)\n", $mailbox), Horde_Imap_Client::DEBUG_INFO);
            return;
        }

        $seq_res = ($data->key_type == $data::UID)
            ? null
            : $this->_getSeqUidLookup($this->getIdsOb($data->ids(), true));

        $tocache = array();

        $status_flags = 0;
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $status_flags |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }
        if (empty($options['uidvalid'])) {
            $status_flags |= Horde_Imap_Client::STATUS_UIDVALIDITY;
        }

        $modseq = null;
        $status_res = $this->status($mailbox, $status_flags);
        $uidvalid = isset($status_res['uidvalidity'])
            ? $status_res['uidvalidity']
            : $options['uidvalid'];
        if (count($data)) {
            $cf = empty($options['fields'])
                ? $this->_params['cache']['fields']
                : $this->_cacheFields();
        }

        foreach ($data as $k => $v) {
            $tmp = array();

            foreach ($cf as $key => $val) {
                if ($v->exists($key)) {
                    switch ($key) {
                    case Horde_Imap_Client::FETCH_ENVELOPE:
                        $tmp[$val] = $v->getEnvelope();
                        break;

                    case Horde_Imap_Client::FETCH_FLAGS:
                        /* A FLAGS FETCH can only occur if we are in the
                         * mailbox. So HIGHESTMODSEQ has already been updated.
                         * Ignore flag caching if MODSEQs not available. */
                        if ($modseq = $status_res['highestmodseq']) {
                            $tmp[$val] = $v->getFlags();
                        }
                        break;

                    case Horde_Imap_Client::FETCH_HEADERS:
                        foreach ($this->_temp['headers_caching'] as $label => $hash) {
                            if ($hdr = $v->getHeaders($label)) {
                                $tmp[$val][$hash] = $hdr;
                            }
                        }
                        break;

                    case Horde_Imap_Client::FETCH_IMAPDATE:
                        $tmp[$val] = $v->getImapDate();
                        break;

                    case Horde_Imap_Client::FETCH_SIZE:
                        $tmp[$val] = $v->getSize();
                        break;

                    case Horde_Imap_Client::FETCH_STRUCTURE:
                        $tmp[$val] = clone $v->getStructure();
                        break;
                    }
                }
            }

            if (!empty($tmp)) {
                $tocache[is_null($seq_res) ? $k : $seq_res['lookup'][$k]] = $tmp;
            }
        }

        $this->_cache->set($mailbox, $tocache, $uidvalid);

        if ($modseq) {
            $metadata = $this->_cache->getMetaData($mailbox, $uidvalid, array(self::CACHE_MODSEQ));
            if (!isset($metadata[self::CACHE_MODSEQ]) ||
                ($metadata[self::CACHE_MODSEQ] != $modseq)) {
                $this->_updateMetaData($mailbox, array(self::CACHE_MODSEQ => $modseq), $uidvalid);
            }
        }
    }

    /**
     * Moves cache entries from one mailbox to another.
     *
     * @param string $from      The source mailbox (UTF7-IMAP).
     * @param string $to        The destination mailbox (UTF7-IMAP).
     * @param array $map        Mapping of source UIDs (keys) to destination
     *                          UIDs (values).
     * @param string $uidvalid  UIDVALIDITY of destination mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _moveCache($from, $to, $map, $uidvalid = null)
    {
        if (!$this->_initCache()) {
            return;
        }

        if (in_array($to, $this->_params['cache']['fetch_ignore'])) {
            $this->writeDebug(sprintf("IGNORING moving cached FETCH data (%s => %s)\n", $from, $to), Horde_Imap_Client::DEBUG_INFO);
            return;
        }

        if (is_null($uidvalid)) {
            $status_res = $this->status($to, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $uidvalid = $status_res['uidvalidity'];
        }

        $old_data = $this->_cache->get($from, array_keys($map), null);
        $new_data = array();

        foreach ($map as $key => $val) {
            if (!empty($old_data[$key])) {
                $new_data[$val] = $old_data[$key];
            }
        }

        if (!empty($new_data)) {
            $this->_cache->set($to, $new_data, $uidvalid);
        }
    }

    /**
     * Delete messages in the cache.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $uids      The list of message UIDs to delete.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _deleteMsgs($mailbox, $uids)
    {
        $this->_cache->deleteMsgs($mailbox, $uids);
    }

    /**
     * Retrieve data from the search cache.
     *
     * @param string $type     The cache type ('search' or 'thread').
     * @param string $mailbox  The mailbox to update.
     * @param array $options   The options array of the calling function.
     *
     * @return mixed  If retrieved, array is returned with data in key 'data'
     *                and the search ID in key 'id'.
     *                If caching is available, returns cacheid string.
     *                Returns null if caching is not available.
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        ksort($options);
        $cache = hash('md5', $type . serialize($options));

        $search_id = $mailbox . $cache;
        $status = $this->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
        $metadata = $this->_cache->getMetaData($mailbox, $status['uidvalidity'], array(self::CACHE_SEARCH));

        $cacheid = $this->getCacheId($mailbox);
        if (isset($metadata[self::CACHE_SEARCH]['cacheid']) &&
            ($metadata[self::CACHE_SEARCH]['cacheid'] != $cacheid)) {
            $metadata[self::CACHE_SEARCH] = array();
            if ($this->_debug &&
                !isset($this->_temp['searchcacheexpire'][strval($mailbox)])) {
                $this->writeDebug(sprintf("Expired search results from cache (mailbox: %s)\n", $mailbox), Horde_Imap_Client::DEBUG_INFO);
                $this->_temp['searchcacheexpire'][strval($mailbox)] = true;
            }
        } elseif (isset($metadata[self::CACHE_SEARCH][$cache])) {
            $this->writeDebug(sprintf("Retrieved %s results from cache (mailbox: %s; id: %s)\n", $type, $mailbox, $cache), Horde_Imap_Client::DEBUG_INFO);

            return array(
                'data' => unserialize($metadata[self::CACHE_SEARCH][$cache]),
                'id' => $search_id
            );
        }

        $metadata[self::CACHE_SEARCH]['cacheid'] = $cacheid;

        $this->_temp['searchcache'][$search_id] = array(
            'id' => $cache,
            'mailbox' => $mailbox,
            'metadata' => $metadata,
            'type' => $type
        );

        return $search_id;
    }

    /**
     * Set data in the search cache.
     *
     * @param mixed $data  The cache data to store.
     * @param string $sid  The search ID returned from _getSearchCache().
     */
    protected function _setSearchCache($data, $sid)
    {
        $cache = &$this->_temp['searchcache'][$sid];
        $cache['metadata'][self::CACHE_SEARCH][$cache['id']] = serialize($data);

        $this->_updateMetaData($cache['mailbox'], $cache['metadata']);

        if ($this->_debug) {
            $this->writeDebug(sprintf("Saved %s results to cache (mailbox: %s; id: %s)\n", $cache['type'], $cache['mailbox'], $cache['id']), Horde_Imap_Client::DEBUG_INFO);
            unset($this->_temp['searchcacheexpire'][strval($cache['mailbox'])]);
        }
    }

    /**
     * Updates metadata for a mailbox.
     *
     * @param string $mailbox    Mailbox to update.
     * @param string $data       The data to update.
     * @param integer $uidvalid  The uidvalidity of the mailbox.  If not set,
     *                           do a status call to grab it.
     */
    protected function _updateMetaData($mailbox, $data, $uidvalid = null)
    {
        if (is_null($uidvalid)) {
            $status = $this->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $uidvalid = $status['uidvalidity'];
        }
        $this->_cache->setMetaData($mailbox, $uidvalid, $data);
    }

    /**
     * Synchronizes the current mailbox cache with the server.
     */
    protected function _syncMailbox()
    {
        $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_UIDVALIDITY | Horde_Imap_Client::STATUS_HIGHESTMODSEQ);

        /* Check that modseqs are available in mailbox. */
        if (empty($status['highestmodseq'])) {
            return;
        }

        /* Grab all flags updated since the cached modseq. */
        $md = $this->_cache->getMetaData($this->_selected, $status['uidvalidity'], array(self::CACHE_MODSEQ));
        if (!isset($md[self::CACHE_MODSEQ]) ||
            ($md[self::CACHE_MODSEQ] == $status['highestmodseq'])) {
            return;
        }

        $uids = $this->_cache->get($this->_selected, array(), array(), $status['uidvalidity']);

        if (!empty($uids)) {
            $uids_ob = $this->getIdsOb($uids);

            /* Are we caching flags? */
            if (array_key_exists(Horde_Imap_Client::FETCH_FLAGS, $this->_cacheFields())) {
                $fquery = new Horde_Imap_Client_Fetch_Query();
                $fquery->flags();

                /* Update flags in cache. Cache will be updated in _fetch(). */
                $this->_fetch(new Horde_Imap_Client_Fetch_Results(), $fquery, array(
                    'changedsince' => $md[self::CACHE_MODSEQ],
                    'ids' => $uids_ob
                ));
            }

            /* Search for deleted messages, and remove from cache. */
            $squery = new Horde_Imap_Client_Search_Query();
            $squery->ids($this->getIdsOb($uids_ob->range_string));

            $search = $this->search($this->_selected, $squery, array(
                'nocache' => true
            ));

            $deleted = array_diff($uids_ob->ids, $search['match']->ids);
            if (!empty($deleted)) {
                $this->_deleteMsgs($this->_selected, $deleted);
            }
        }

        $this->_updateMetaData($this->_selected, array(
            self::CACHE_MODSEQ => $status['highestmodseq']
        ), $status['uidvalidity']);
    }

    /**
     * Provide the list of available caching fields.
     *
     * @return array  The list of available caching fields (fields are in the
     *                key).
     */
    protected function _cacheFields()
    {
        $out = $this->_params['cache']['fields'];

        if (!isset($this->_init['enabled']['CONDSTORE'])) {
            unset($out[Horde_Imap_Client::FETCH_FLAGS]);
        }

        return $out;
    }

}
