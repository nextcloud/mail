<?php
/**
 * An interface to an IMAP4rev1 server (RFC 3501) using built-in PHP features.
 *
 * Implements the following IMAP-related RFCs (see
 * http://www.iana.org/assignments/imap4-capabilities):
 *   - RFC 2086/4314: ACL
 *   - RFC 2087: QUOTA
 *   - RFC 2088: LITERAL+
 *   - RFC 2195: AUTH=CRAM-MD5
 *   - RFC 2221: LOGIN-REFERRALS
 *   - RFC 2342: NAMESPACE
 *   - RFC 2595/4616: TLS & AUTH=PLAIN
 *   - RFC 2831: DIGEST-MD5 authentication mechanism (obsoleted by RFC 6331)
 *   - RFC 2971: ID
 *   - RFC 3348: CHILDREN
 *   - RFC 3501: IMAP4rev1 specification
 *   - RFC 3502: MULTIAPPEND
 *   - RFC 3516: BINARY
 *   - RFC 3691: UNSELECT
 *   - RFC 4315: UIDPLUS
 *   - RFC 4422: SASL Authentication (for DIGEST-MD5)
 *   - RFC 4466: Collected extensions (updates RFCs 2088, 3501, 3502, 3516)
 *   - RFC 4469/5550: CATENATE
 *   - RFC 4551: CONDSTORE
 *   - RFC 4731: ESEARCH
 *   - RFC 4959: SASL-IR
 *   - RFC 5032: WITHIN
 *   - RFC 5161: ENABLE
 *   - RFC 5162: QRESYNC
 *   - RFC 5182: SEARCHRES
 *   - RFC 5255: LANGUAGE/I18NLEVEL
 *   - RFC 5256: THREAD/SORT
 *   - RFC 5258: LIST-EXTENDED
 *   - RFC 5267: ESORT; PARTIAL search return option
 *   - RFC 5464: METADATA
 *   - RFC 5530: IMAP Response Codes
 *   - RFC 5819: LIST-STATUS
 *   - RFC 5957: SORT=DISPLAY
 *   - RFC 6154: SPECIAL-USE/CREATE-SPECIAL-USE
 *   - RFC 6203: SEARCH=FUZZY
 *
 * Implements the following non-RFC extensions:
 * <ul>
 *  <li>draft-ietf-morg-inthread-01: THREAD=REFS</li>
 *  <li>draft-daboo-imap-annotatemore-07: ANNOTATEMORE</li>
 *  <li>draft-daboo-imap-annotatemore-08: ANNOTATEMORE2</li>
 *  <li>XIMAPPROXY
 *   <ul>
 *    <li>Requires imapproxy v1.2.7-rc1 or later</li>
 *    <li>
 *     See https://squirrelmail.svn.sourceforge.net/svnroot/squirrelmail/trunk/imap_proxy/README
 *    </li>
 *   </ul>
 *  </li>
 * </ul>
 *
 * TODO (or not necessary?):
 * <ul>
 *  <li>RFC 2177: IDLE
 *   <ul>
 *    <li>
 *     Probably not necessary due to the limited connection time of each
 *     HTTP/PHP request
 *    </li>
 *   </ul>
 *  <li>RFC 2193: MAILBOX-REFERRALS</li>
 *  <li>
 *   RFC 4467/5092/5524/5550/5593: URLAUTH, URLAUTH=BINARY, URL-PARTIAL
 *  </li>
 *  <li>RFC 4978: COMPRESS=DEFLATE
 *   <ul>
 *    <li>See: http://bugs.php.net/bug.php?id=48725</li>
 *   </ul>
 *  </li>
 *  <li>RFC 5257: ANNOTATE (Experimental)</li>
 *  <li>RFC 5259: CONVERT</li>
 *  <li>RFC 5267: CONTEXT=SEARCH; CONTEXT=SORT</li>
 *  <li>RFC 5465: NOTIFY</li>
 *  <li>RFC 5466: FILTERS</li>
 *  <li>RFC 5738: UTF8 (Very limited support currently)</li>
 *  <li>RFC 6237: MULTISEARCH</li>
 *  <li>draft-ietf-morg-inthread-01: SEARCH=INTHREAD
 *   <ul>
 *    <li>Appears to be dead</li>
 *   </ul>
 *  </li>
 *  <li>draft-krecicki-imap-move-01.txt: MOVE
 *   <ul>
 *    <li>Appears to be dead</li>
 *   </ul>
 *  </li>
 * </ul>
 *
 * Originally based on code from:
 *   - auth.php (1.49)
 *   - imap_general.php (1.212)
 *   - imap_messages.php (revision 13038)
 *   - strings.php (1.184.2.35)
 * from the Squirrelmail project.
 * Copyright (c) 1999-2007 The SquirrelMail Project Team
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
class Horde_Imap_Client_Socket extends Horde_Imap_Client_Base
{
    /**
     * The socket connection to the IMAP server.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * The unique tag to use when making an IMAP query.
     *
     * @var integer
     */
    protected $_tag = 0;

    /**
     * @param array $params  A hash containing configuration parameters.
     *                       Additional parameters to base driver:
     *   - debug_literal: (boolean) If true, will output the raw text of
     *                    literal responses to the debug stream. Otherwise,
     *                    outputs a summary of the literal response.
     *   - envelope_addrs: (integer) The maximum number of address entries to
     *                     read for FETCH ENVELOPE address fields.
     *                     DEFAULT: 1000
     *   * envelope_string: (integer) The maximum length of string fields
     *                      returned by the FETCH ENVELOPE command.
     *                      DEFAULT: 2048
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'debug_literal' => false,
            'envelope_addrs' => 1000,
            'envelope_string' => 2048
        ), $params);

        parent::__construct($params);
    }

    /**
     */
    protected function _capability()
    {
        // Need to use connect call here or else we run into loop issues
        // because _connect() can call capability() internally.
        $this->_connect();

        // It is possible the server provided capability information on
        // connect, so check for it now.
        if (!isset($this->_init['capability'])) {
            $this->_sendLine($this->_clientCommand('CAPABILITY'));
        }

        return isset($this->_init['capability'])
            ? $this->_init['capability']
            : array();
    }

    /**
     * Parse a CAPABILITY Response (RFC 3501 [7.2.1]).
     *
     * @param array $data  An array of CAPABILITY strings.
     */
    protected function _parseCapability($data)
    {
        if (!empty($this->_temp['no_cap'])) {
            unset($this->_temp['no_cap']);
            return;
        }

        if (empty($this->_temp['in_login'])) {
            $c = array();
        } else {
            $c = $this->_init['capability'];
            $this->_temp['logincapset'] = true;
        }

        foreach ($data as $val) {
            $cap_list = explode('=', $val);
            $cap_list[0] = strtoupper($cap_list[0]);
            if (isset($cap_list[1])) {
                if (!isset($c[$cap_list[0]]) || !is_array($c[$cap_list[0]])) {
                    $c[$cap_list[0]] = array();
                }
                $c[$cap_list[0]][] = $cap_list[1];
            } elseif (!isset($c[$cap_list[0]])) {
                $c[$cap_list[0]] = true;
            }
        }

        $this->_setInit('capability', $c);
    }

    /**
     */
    protected function _noop()
    {
        // NOOP doesn't return any specific response
        $this->_sendLine($this->_clientCommand('NOOP'));
    }

    /**
     */
    protected function _getNamespaces()
    {
        if (!$this->queryCapability('NAMESPACE')) {
            return array();
        }

        $this->_sendLine($this->_clientCommand('NAMESPACE'));

        return $this->_temp['namespace'];
    }

    /**
     * Parse a NAMESPACE response (RFC 2342 [5] & RFC 5255 [3.4]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The NAMESPACE data.
     */
    protected function _parseNamespace(Horde_Imap_Client_Tokenize $data)
    {
        $namespace_array = array(
            Horde_Imap_Client::NS_PERSONAL,
            Horde_Imap_Client::NS_OTHER,
            Horde_Imap_Client::NS_SHARED
        );

        $c = &$this->_temp['namespace'];
        $c = array();

        // Per RFC 2342, response from NAMESPACE command is:
        // (PERSONAL NAMESPACES) (OTHER_USERS NAMESPACE) (SHARED NAMESPACES)
        foreach ($namespace_array as $val) {
            $entry = $data->current();

            if (is_null($entry)) {
                continue;
            }

            foreach ($entry as $v) {
                $ob = Horde_Imap_Client_Mailbox::get($v->rewind(), true);

                $c[strval($ob)] = array(
                    'delimiter' => $v->next(),
                    'hidden' => false,
                    'name' => strval($ob),
                    'translation' => '',
                    'type' => $val
                );

                // RFC 4466: NAMESPACE extensions
                while (($ext = $v->next()) !== false) {
                    switch (strtoupper($ext)) {
                    case 'TRANSLATION':
                        // RFC 5255 [3.4] - TRANSLATION extension
                        $c[strval($ob)]['translation'] = $v->next()->rewind();
                        break;
                    }
                }
            }

            $data->next();
        }
    }

    /**
     */
    public function alerts()
    {
        $alerts = empty($this->_temp['alerts'])
            ? array()
            : $this->_temp['alerts'];
        $this->_temp['alerts'] = array();
        return $alerts;
    }

    /**
     */
    protected function _login()
    {
        if (!empty($this->_temp['preauth'])) {
            unset($this->_temp['preauth']);
            return $this->_loginTasks();
        }

        $this->_connect();

        $first_login = empty($this->_init['authmethod']);
        $t = &$this->_temp;

        // Switch to secure channel if using TLS.
        if (!$this->_isSecure &&
            ($this->_params['secure'] == 'tls')) {
            if ($first_login && !$this->queryCapability('STARTTLS')) {
                // We should never hit this - STARTTLS is required pursuant
                // to RFC 3501 [6.2.1].
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Server does not support TLS connections."),
                    Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                );
            }

            // Switch over to a TLS connection.
            // STARTTLS returns no untagged response.
            $this->_sendLine($this->_clientCommand('STARTTLS'));

            if (@stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                $this->logout();
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Could not open secure TLS connection to the IMAP server."),
                    Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                );
            }

            if ($first_login) {
                // Expire cached CAPABILITY information (RFC 3501 [6.2.1])
                $this->_setInit('capability');

                // Reset language (RFC 5255 [3.1])
                $this->_setInit('lang');
            }

            // Set language if using imapproxy
            if (!empty($this->_init['imapproxy'])) {
                $this->setLanguage();
            }

            $this->_isSecure = true;
        }

        if ($first_login) {
            $imap_auth_mech = array();

            $auth_methods = $this->queryCapability('AUTH');
            if (!empty($auth_methods)) {
                // Add SASL methods. Prefer CRAM-MD5 over DIGEST-MD5, as the
                // latter has been obsoleted (RFC 6331).
                $imap_auth_mech = array_intersect(array('CRAM-MD5', 'DIGEST-MD5'), $auth_methods);

                // Next, try 'PLAIN' authentication.
                if (in_array('PLAIN', $auth_methods)) {
                    $imap_auth_mech[] = 'PLAIN';
                }
            }

            // Fall back to 'LOGIN' if available.
            if (!$this->queryCapability('LOGINDISABLED')) {
                $imap_auth_mech[] = 'LOGIN';
            }

            if (empty($imap_auth_mech)) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("No supported IMAP authentication method could be found."),
                    Horde_Imap_Client_Exception::LOGIN_NOAUTHMETHOD
                );
            }

            /* Use MD5 authentication first, if available. But no need to use
             * special authentication if we are already using an encrypted
             * connection. */
            if ($this->_isSecure) {
                $imap_auth_mech = array_reverse($imap_auth_mech);
            }
        } else {
            $imap_auth_mech = array($this->_init['authmethod']);
        }

        /* Default to AUTHENTICATIONFAILED error (see RFC 5530[3]). */
        $t['loginerr'] = new Horde_Imap_Client_Exception(
            Horde_Imap_Client_Translation::t("Mail server denied authentication."),
            Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
        );

        foreach ($imap_auth_mech as $method) {
            $t['referral'] = null;

            /* Set a flag indicating whether we have received a CAPABILITY
             * response after we successfully login. Since capabilities may
             * be different after login, we need to merge this information into
             * the current CAPABILITY array (since some servers, e.g. Cyrus,
             * may not include authentication capabilities that are still
             * needed in the event this object is eventually serialized). */
            $this->_temp['in_login'] = true;

            try {
                $this->_tryLogin($method);
                $success = true;
                $this->_setInit('authmethod', $method);
                unset($t['referralcount']);
            } catch (Horde_Imap_Client_Exception $e) {
                $success = false;
            }

            unset($this->_temp['in_login']);

            // Check for login referral (RFC 2221) response - can happen for
            // an OK, NO, or BYE response.
            if (!is_null($t['referral'])) {
                foreach (array('hostspec', 'port', 'username') as $val) {
                    if (!is_null($t['referral']->$val)) {
                        $this->_params[$val] = $t['referral']->$val;
                    }
                }

                if (!is_null($t['referral']->auth)) {
                    $this->_setInit('authmethod', $t['referral']->auth);
                }

                if (!isset($t['referralcount'])) {
                    $t['referralcount'] = 0;
                }

                // RFC 2221 [3] - Don't follow more than 10 levels of referral
                // without consulting the user.
                if (++$t['referralcount'] < 10) {
                    $this->logout();
                    $this->_setInit('capability');
                    $this->_setInit('namespace', array());
                    return $this->login();
                }

                unset($t['referralcount']);
            }

            if ($success) {
                return $this->_loginTasks($first_login);
            }
        }

        $ex = $t['loginerr'];

        /* Try again from scratch if authentication failed in an established,
         * previously-authenticated object. */
        if (!empty($this->_init['authmethod'])) {
            $this->_setInit();
            try {
                return $this->login();
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        throw $ex;
    }

    /**
     * Connects to the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _connect()
    {
        if (!is_null($this->_stream)) {
            return;
        }

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            throw new InvalidArgumentException('Secure connections require the PHP openssl extension.');
        }

        switch ($this->_params['secure']) {
        case 'ssl':
        case 'sslv2':
        case 'sslv3':
            $conn = $this->_params['secure'] . '://';
            $this->_isSecure = true;
            break;

        case 'tls':
        default:
            $conn = 'tcp://';
            break;
        }

        $this->_stream = @stream_socket_client($conn . $this->_params['hostspec'] . ':' . $this->_params['port'], $error_number, $error_string, $this->_params['timeout']);

        if ($this->_stream === false) {
            $this->_stream = null;
            $this->_isSecure = false;
            $e = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error connecting to mail server."),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
            $e->details = sprintf("[%u] %s", $error_number, $error_string);
            throw $e;
        }

        stream_set_timeout($this->_stream, $this->_params['timeout']);

        // If we already have capability information, don't re-set with
        // (possibly) limited information sent in the inital banner.
        if (isset($this->_init['capability'])) {
            $this->_temp['no_cap'] = true;
        }

        /* Get greeting information.  This is untagged so we need to specially
         * deal with it here. */
        try {
            $this->_getLine();
        } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
            if ($e->status == Horde_Imap_Client_Interaction_Server::BYE) {
                /* Server is explicitly rejecting our connection (RFC 3501
                 * [7.1.5]). */
                $e->setMessage(Horde_Imap_Client_Translation::t("Server rejected connection."));
                $e->setCode(Horde_Imap_Client_Exception::SERVER_CONNECT);
            }
            throw $e;
        }

        // Check for IMAP4rev1 support
        if (!$this->queryCapability('IMAP4REV1')) {
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("The mail server does not support IMAP4rev1 (RFC 3501)."),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
        }

        // Set language if NOT using imapproxy
        if (empty($this->_init['imapproxy'])) {
            if ($this->queryCapability('XIMAPPROXY')) {
                $this->_setInit('imapproxy', true);
            } else {
                $this->setLanguage();
            }
        }

        // If pre-authenticated, we need to do all login tasks now.
        if (!empty($this->_temp['preauth'])) {
            $this->login();
        }
    }

    /**
     * Authenticate to the IMAP server.
     *
     * @param string $method  IMAP login method.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _tryLogin($method)
    {
        switch ($method) {
        case 'CRAM-MD5':
        case 'CRAM-SHA1':
        case 'CRAM-SHA256':
            // RFC 2195: CRAM-MD5
            // CRAM-SHA1 & CRAM-SHA256 supported by Courier SASL library
            $ob = $this->_sendLine(
                $this->_clientCommand(array('AUTHENTICATE', $method))
            );

            $cmd = new Horde_Imap_Client_Data_Format_List(
                base64_encode($this->_params['username'] . ' ' . hash_hmac(strtolower(substr($method, 5)), base64_decode($ob->token->current()), $this->getParam('password'), false))
            );
            $this->_sendLine($cmd, array(
                'debug' => '[' . $method . ' Response]'
            ));
            break;

        case 'DIGEST-MD5':
            // RFC 2831/4422; obsoleted by RFC 6331
            $ob = $this->_sendLine(
                $this->_clientCommand(array('AUTHENTICATE', $method))
            );

            $cmd = new Horde_Imap_Client_Data_Format_List(
                base64_encode(new Horde_Imap_Client_Auth_DigestMD5(
                    $this->_params['username'],
                    $this->getParam('password'),
                    base64_decode($ob->token->current()),
                    $this->_params['hostspec'],
                    'imap'
                ))
            );
            $ob = $this->_sendLine($cmd, array(
                'debug' => '[DIGEST-MD5 Response]'
            ));

            if (strpos(base64_decode($ob->token->current()), 'rspauth=') === false) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Unexpected response from server when authenticating."),
                    Horde_Imap_Client_Exception::SERVER_CONNECT
                );
            }
            $this->_sendLine(new Horde_Imap_Client_Data_Format_List());
            break;

        case 'LOGIN':
            $cmd = $this->_clientCommand(array(
                'LOGIN',
                new Horde_Imap_Client_Data_Format_Astring($this->_params['username']),
                new Horde_Imap_Client_Data_Format_Astring($this->getParam('password'))
            ));
            $this->_sendLine($cmd, array(
                'debug' => sprintf('[LOGIN Command - username: %s]', $this->_params['username'])
            ));
            break;

        case 'PLAIN':
            // RFC 2595/4616 - PLAIN SASL mechanism
            $auth = base64_encode(implode("\0", array($this->_params['username'], $this->_params['username'], $this->getParam('password'))));
            $cmd = $this->_clientCommand(array(
                'AUTHENTICATE',
                'PLAIN'
            ));

            if ($this->queryCapability('SASL-IR')) {
                // IMAP Extension for SASL Initial Client Response (RFC 4959)
                $cmd->add($auth);
                $this->_sendLine($cmd, array(
                    'debug' => sprintf('[SASL-IR AUTHENTICATE Command - username: %s]', $this->_params['username'])
                ));
            } else {
                $this->_sendLine($cmd);

                $cmd = new Horde_Imap_Client_Data_Format_List($auth);
                $this->_sendLine($cmd, array(
                    'debug' => sprintf('[AUTHENTICATE Command - username: %s]', $this->_params['username'])
                ));
            }
            break;

        default:
            throw new Horde_Imap_Client_Exception(
                sprintf(Horde_Imap_Client_Translation::t("Unknown authentication method: %s"), $method),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
        }
    }

    /**
     * Perform login tasks.
     *
     * @param boolean $firstlogin  Is this the first login?
     *
     * @return boolean  True if global login tasks should be performed.
     */
    protected function _loginTasks($firstlogin = true)
    {
        /* If reusing an imapproxy connection, no need to do any of these
         * login tasks again. */
        if (!$firstlogin && !empty($this->_temp['proxyreuse'])) {
            // If we have not yet set the language, set it now.
            if (!isset($this->_init['lang'])) {
                $this->setLanguage();
            }
            return false;
        }

        $this->_setInit('enabled', array());

        /* If we logged in for first time, and server did not return
         * capability information, we need to grab it now. */
        if ($firstlogin && empty($this->_temp['logincapset'])) {
            $this->_setInit('capability');
        }
        $this->setLanguage();

        /* Only active QRESYNC/CONDSTORE if caching is enabled. */
        if ($this->_initCache()) {
            if ($this->queryCapability('QRESYNC')) {
                $this->_enable(array('QRESYNC'));
            } elseif ($this->queryCapability('CONDSTORE')) {
                $this->_enable(array('CONDSTORE'));
            }
        }

        return true;
    }

    /**
     */
    protected function _logout()
    {
        if (!is_null($this->_stream)) {
            if (empty($this->_temp['logout'])) {
                $this->_temp['logout'] = true;
                try {
                    $this->_sendLine($this->_clientCommand('LOGOUT'));
                } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                    // Ignore server errors
                }
            }
            unset($this->_temp['logout']);
            @fclose($this->_stream);
            $this->_stream = null;
        }

        unset($this->_temp['proxyreuse']);
    }

    /**
     */
    protected function _sendID($info)
    {
        $cmd = $this->_clientCommand('ID');

        if (empty($info)) {
            $cmd->add(new Horde_Imap_Client_Data_Format_Nil());
        } else {
            $tmp = new Horde_Imap_Client_Data_Format_List();
            foreach ($info as $key => $val) {
                $tmp->add(array(
                    new Horde_Imap_Client_Data_Format_String(strtolower($key)),
                    new Horde_Imap_Client_Data_Format_Nstring($val)
                ));
            }
            $cmd->add($tmp);
        }

        $this->_sendLine($cmd);
    }

    /**
     * Parse an ID response (RFC 2971 [3.2])
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseID(Horde_Imap_Client_Tokenize $data)
    {
        $this->_temp['id'] = array();

        if (is_null($token_data = $data->current())) {
            return;
        }

        $curr = $token_data->rewind();
        do {
            if (!is_null($id = $token_data->next())) {
                $this->_temp['id'][$curr] = $id;
            }
        } while (($curr = $token_data->next()) !== false);
    }

    /**
     */
    protected function _getID()
    {
        if (!isset($this->_temp['id'])) {
            $this->sendID();
        }
        return $this->_temp['id'];
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        $cmd = $this->_clientCommand('LANGUAGE');
        foreach ($langs as $lang) {
            $cmd->add(new Horde_Imap_Client_Data_Format_Astring($lang));
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_setInit('lang', false);
            return null;
        }

        return $this->_init['lang'];
    }

    /**
     */
    protected function _getLanguage($list)
    {
        if (!$list) {
            return empty($this->_init['lang'])
                ? null
                : $this->_init['lang'];
        }

        if (!isset($this->_init['langavail'])) {
            try {
                $this->_sendLine($this->_clientCommand('LANGUAGE'));
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_setInit('langavail', array());
            }
        }

        return $this->_init['langavail'];
    }

    /**
     * Parse a LANGUAGE response (RFC 5255 [3.3]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseLanguage(Horde_Imap_Client_Tokenize $data)
    {
        $lang_list = iterator_to_array($data->current());

        if (count($lang_list) == 1) {
            // This is the language that was set.
            $this->_setInit('lang', reset($lang_list));
        } else {
            // These are the languages that are available.
            $this->_setInit('langavail', $lang_list);
        }
    }

    /**
     * Enable an IMAP extension (see RFC 5161).
     *
     * @param array $exts  The extensions to enable.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _enable($exts)
    {
        if ($this->queryCapability('ENABLE')) {
            // Only enable non-enabled extensions
            $exts = array_diff($exts, array_keys($this->_init['enabled']));
            if (!empty($exts)) {
                $cmd = $this->_clientCommand(array(
                    'ENABLE',
                    $exts
                ));
                $this->_sendLine($cmd);
            }
        }
    }

    /**
     * Parse an ENABLED response (RFC 5161 [3.2]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseEnabled(Horde_Imap_Client_Tokenize $data)
    {
        $this->_setInit('enabled', array_merge(
            $this->_init['enabled'],
            array_flip($data->flushIterator())
        ));
    }

    /**
     */
    protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox, $mode)
    {
        $qresync = isset($this->_init['enabled']['QRESYNC']);

        /* Let the 'CLOSE' response code handle mailbox switching if QRESYNC
         * is active. */
        if (!isset($this->_temp['mailbox']['name']) ||
            (!$qresync && ($mailbox != $this->_temp['mailbox']['name']))) {
            $this->_temp['mailbox'] = array('name' => clone($mailbox));
            $this->_selected = clone($mailbox);
        } elseif ($qresync) {
            $this->_temp['qresyncmbox'] = clone($mailbox);
        }

        $cmd = $this->_clientCommand(array(
            ($mode == Horde_Imap_Client::OPEN_READONLY) ? 'EXAMINE' : 'SELECT',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));

        /* If QRESYNC is available, synchronize the mailbox. */
        if ($qresync) {
            $this->_initCache();
            $metadata = $this->_cache->getMetaData($mailbox, null, array(self::CACHE_MODSEQ, 'uidvalid'));

            if (isset($metadata[self::CACHE_MODSEQ])) {
                $uids = $this->_cache->get($mailbox);
                if (!empty($uids)) {
                    /* This command may cause several things to happen.
                     * 1. UIDVALIDITY may have changed.  If so, we need
                     * to expire the cache immediately (done below).
                     * 2. NOMODSEQ may have been returned. We can keep current
                     * message cache data but won't be able to do flag
                     * caching.
                     * 3. VANISHED/FETCH information was returned. These
                     * responses will have already been handled by those
                     * response handlers.
                     * TODO: Use 4th parameter (useful if we keep a sequence
                     * number->UID lookup in the future). */
                    $cmd->add(new Horde_Imap_Client_Data_Format_List(array(
                        'QRESYNC',
                        new Horde_Imap_Client_Data_Format_List(array(
                            $metadata['uidvalid'],
                            $metadata[self::CACHE_MODSEQ],
                            $this->getIdsOb($uids)->tostring_sort
                        ))
                    )));
                }
            }
        } elseif (!isset($this->_init['enabled']['CONDSTORE']) &&
                  $this->_initCache() &&
                  $this->queryCapability('CONDSTORE')) {
            /* Activate CONDSTORE now if ENABLE is not available. */
            $cmd->add(new Horde_Imap_Client_Data_Format_List('CONDSTORE'));
            $this->_setInit('enabled', array_merge(
                $this->_init['enabled'],
                array('CONDSTORE' => true)
            ));
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
            // An EXAMINE/SELECT failure with a return of 'NO' will cause the
            // current mailbox to be unselected.
            if ($e->status == Horde_Imap_Client_Interaction_Server::NO) {
                $this->_selected = null;
                $this->_mode = 0;
                if (!$e->getCode()) {
                    throw new Horde_Imap_Client_Exception(
                        sprintf(Horde_Imap_Client_Translation::t("Could not open mailbox \"%s\"."), $mailbox),
                        Horde_Imap_Client_Exception::MAILBOX_NOOPEN
                    );
                }
            }
            throw $e;
        }
    }

    /**
     */
    protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox, $opts)
    {
        $cmd = $this->_clientCommand(array(
            'CREATE',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));

        if (!empty($opts['special_use'])) {
            $cmd->add(array(
                'USE',
                new Horde_Imap_Client_Data_Format_List($opts['special_use'])
            ));
        }

        // CREATE returns no untagged information (RFC 3501 [6.3.3])
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox)
    {
        // Some IMAP servers will not allow a delete of a currently open
        // mailbox.
        if ($mailbox->equals($this->_selected)) {
            $this->close();
        }

        try {
            // DELETE returns no untagged information (RFC 3501 [6.3.4])
            $cmd = $this->_clientCommand(array(
                'DELETE',
                new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
            ));
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            // Some IMAP servers won't allow a mailbox delete unless all
            // messages in that mailbox are deleted.
            if (!empty($this->_temp['deleteretry'])) {
                unset($this->_temp['deleteretry']);
                throw $e;
            }

            $this->store($mailbox, array('add' => array(Horde_Imap_Client::FLAG_DELETED)));
            $this->expunge($mailbox);

            $this->_temp['deleteretry'] = true;
            $this->deleteMailbox($mailbox);
        }

        unset($this->_temp['deleteretry']);
    }

    /**
     */
    protected function _renameMailbox(Horde_Imap_Client_Mailbox $old,
                                      Horde_Imap_Client_Mailbox $new)
    {
        // RENAME returns no untagged information (RFC 3501 [6.3.5])
        $cmd = $this->_clientCommand(array(
            'RENAME',
            new Horde_Imap_Client_Data_Format_Mailbox($old),
            new Horde_Imap_Client_Data_Format_Mailbox($new)
        ));
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                         $subscribe)
    {
        // SUBSCRIBE/UNSUBSCRIBE returns no untagged information (RFC 3501
        // [6.3.6 & 6.3.7])
        $cmd = $this->_clientCommand(array(
            $subscribe ? 'SUBSCRIBE' : 'UNSUBSCRIBE',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        // RFC 5258 [3.1]: Use LSUB for MBOX_SUBSCRIBED if no other server
        // return options are specified.
        if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) &&
            empty($options['attributes']) &&
            empty($options['children']) &&
            empty($options['recursivematch']) &&
            empty($options['remote']) &&
            empty($options['special_use']) &&
            empty($options['status'])) {
            return $this->_getMailboxList(
                $pattern,
                Horde_Imap_Client::MBOX_SUBSCRIBED,
                array(
                    'delimiter' => !empty($options['delimiter']),
                    'flat' => !empty($options['flat']),
                    'no_listext' => true
                )
            );
        }

        // Get the list of subscribed/unsubscribed mailboxes. Since LSUB is
        // not guaranteed to have correct attributes, we must use LIST to
        // ensure we receive the correct information.
        if (($mode != Horde_Imap_Client::MBOX_ALL) &&
            !$this->queryCapability('LIST-EXTENDED')) {
            $subscribed = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true));

            // If mode is subscribed, and 'flat' option is true, we can
            // return now.
            if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) &&
                !empty($options['flat'])) {
                return $subscribed;
            }
        } else {
            $subscribed = null;
        }

        return $this->_getMailboxList($pattern, $mode, $options, $subscribed);
    }

    /**
     * Obtain a list of mailboxes.
     *
     * @param mixed $pattern     The mailbox search pattern(s).
     * @param integer $mode      Which mailboxes to return.
     * @param array $options     Additional options. 'no_listext' will skip
     *                           using the LIST-EXTENDED capability.
     * @param array $subscribed  A list of subscribed mailboxes.
     *
     * @return array  See listMailboxes(().
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMailboxList($pattern, $mode, $options,
                                       $subscribed = null)
    {
        $check = (($mode != Horde_Imap_Client::MBOX_ALL) && !is_null($subscribed));

        // Setup cache entry for use in _parseList()
        $t = &$this->_temp;
        $t['mailboxlist'] = array(
            'check' => $check,
            'ext' => false,
            'options' => $options,
            'subexist' => ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS),
            'subscribed' => ($check ? array_flip(array_map('strval', $subscribed)) : null)
        );
        $t['listresponse'] = array();
        $return_opts = new Horde_Imap_Client_Data_Format_List();

        if ($this->queryCapability('LIST-EXTENDED') &&
            empty($options['no_listext'])) {
            $cmd = $this->_clientCommand('LIST');
            $t['mailboxlist']['ext'] = true;

            $select_opts = new Horde_Imap_Client_Data_Format_List();

            if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) ||
                ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS)) {
                $select_opts->add('SUBSCRIBED');
                $return_opts->add('SUBSCRIBED');
            }

            if (!empty($options['remote'])) {
                $select_opts->add('REMOTE');
            }

            if (!empty($options['recursivematch'])) {
                $select_opts->add('RECURSIVEMATCH');
            }

            $cmd->add(array(
                $select_opts,
                ''
            ));

            if (!is_array($pattern)) {
                $pattern = array($pattern);
            }
            $tmp = new Horde_Imap_Client_Data_Format_List();
            foreach ($pattern as $val) {
                $tmp->add(new Horde_Imap_Client_Data_Format_ListMailbox($val));
            }
            $cmd->add($tmp);

            if (!empty($options['children'])) {
                $return_opts->add('CHILDREN');
            }

            if (!empty($options['special_use'])) {
                $return_opts->add('SPECIAL-USE');
            }
        } else {
            if (is_array($pattern)) {
                $return_array = array();
                foreach ($pattern as $val) {
                    $return_array = array_merge($return_array, $this->_getMailboxList($val, $mode, $options, $subscribed));
                }
                return $return_array;
            }

            $cmd = $this->_clientCommand(array(
                ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) ? 'LSUB' : 'LIST',
                '',
                new Horde_Imap_Client_Data_Format_ListMailbox($pattern)
            ));
        }

        /* LIST-STATUS does NOT depend on LIST-EXTENDED. */
        if (!empty($options['status']) &&
            $this->queryCapability('LIST-STATUS')) {
            $status_mask = array(
                Horde_Imap_Client::STATUS_MESSAGES => 'MESSAGES',
                Horde_Imap_Client::STATUS_RECENT => 'RECENT',
                Horde_Imap_Client::STATUS_UIDNEXT => 'UIDNEXT',
                Horde_Imap_Client::STATUS_UIDVALIDITY => 'UIDVALIDITY',
                Horde_Imap_Client::STATUS_UNSEEN => 'UNSEEN',
                Horde_Imap_Client::STATUS_HIGHESTMODSEQ => 'HIGHESTMODSEQ'
            );

            $status_opts = new Horde_Imap_Client_Data_Format_List();
            foreach ($status_mask as $key => $val) {
                if ($options['status'] & $key) {
                    $status_opts->add($val);
                }
            }

            if (count($status_opts)) {
                $return_opts->add(array(
                    'STATUS',
                    $status_opts
                ));
            }
        }

        if (count($return_opts)) {
            $cmd->add(array(
                'RETURN',
                $return_opts
            ));
        }

        $this->_sendLine($cmd);

        if (!empty($options['flat'])) {
            return array_values($t['listresponse']);
        }

        /* Add in STATUS return, if needed. */
        if (!empty($options['status'])) {
            if (!is_array($pattern)) {
                $pattern = array($pattern);
            }

            foreach ($pattern as $val) {
                $val_utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($val);
                if (isset($t['listresponse'][$val_utf8]) &&
                    isset($t['status'][$val_utf8])) {
                    $t['listresponse'][$val_utf8]['status'] = $t['status'][$val_utf8];
                }
            }
        }

        return $t['listresponse'];
    }

    /**
     * Parse a LIST/LSUB response (RFC 3501 [7.2.2 & 7.2.3]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response (includes
     *                                          type as first token).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseList(Horde_Imap_Client_Tokenize $data)
    {
        $ml = $this->_temp['mailboxlist'];
        $mlo = $ml['options'];
        $lr = &$this->_temp['listresponse'];

        $attr = iterator_to_array($data->next());
        $delimiter = $data->next();
        $mbox = Horde_Imap_Client_Mailbox::get($data->next(), true);

        if ($ml['check'] &&
            $ml['subexist'] &&
            !isset($ml['subscribed'][$mbox->utf7imap])) {
            return;
        } elseif ((!$ml['check'] && $ml['subexist']) ||
                  (empty($mlo['flat']) && !empty($mlo['attributes']))) {
            $attr = array_flip(array_map('strtolower', $attr));
            if ($ml['subexist'] &&
                !$ml['check'] &&
                isset($attr['\\nonexistent'])) {
                return;
            }
        }

        if (empty($mlo['flat'])) {
            $tmp = array(
                'mailbox' => $mbox
            );

            if (!empty($mlo['attributes'])) {
                /* RFC 5258 [3.4]: inferred attributes. */
                if ($ml['ext']) {
                    if (isset($attr['\\noinferiors'])) {
                        $attr['\\hasnochildren'] = 1;
                    }
                    if (isset($attr['\\nonexistent'])) {
                        $attr['\\noselect'] = 1;
                    }
                }
                $tmp['attributes'] = array_keys($attr);
            }
            if (!empty($mlo['delimiter'])) {
                $tmp['delimiter'] = $delimiter;
            }
            if (($extended = $data->next()) !== false) {
                $tmp['extended'] = $extended;
            }
            $lr[strval($mbox)] = $tmp;
        } else {
            $lr[] = $mbox;
        }
    }

    /**
     */
    protected function _status(Horde_Imap_Client_Mailbox $mailbox, $flags)
    {
        $data = array();
        $query = new Horde_Imap_Client_Data_Format_List();
        $search = null;

        $items = array(
            Horde_Imap_Client::STATUS_MESSAGES => 'messages',
            Horde_Imap_Client::STATUS_RECENT => 'recent',
            Horde_Imap_Client::STATUS_UIDNEXT => 'uidnext',
            Horde_Imap_Client::STATUS_UIDVALIDITY => 'uidvalidity',
            Horde_Imap_Client::STATUS_UNSEEN => 'unseen',
            Horde_Imap_Client::STATUS_FIRSTUNSEEN => 'firstunseen',
            Horde_Imap_Client::STATUS_FLAGS => 'flags',
            Horde_Imap_Client::STATUS_PERMFLAGS => 'permflags',
            Horde_Imap_Client::STATUS_UIDNOTSTICKY => 'uidnotsticky',
        );

        /* Don't include modseq returns if server does not support it. */
        if ($this->queryCapability('CONDSTORE')) {
            $items[Horde_Imap_Client::STATUS_HIGHESTMODSEQ] = 'highestmodseq';

            /* Even though CONDSTORE is available, it may not yet have been
             * enabled. */
            if (($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) &&
                !isset($this->_init['enabled']['CONDSTORE'])) {
                $this->_setInit('enabled', array_merge(
                    $this->_init['enabled'],
                    array('CONDSTORE' => true)
                ));
            }

            /* If highestmodseq for the current mailbox is -1, and that is
             * the mailbox we are querying, then we need to close the current
             * mailbox; CONDSTORE is preventing us from getting the updated
             * value within the current mailbox. */
            if ($mailbox->equals($this->_selected) &&
                isset($this->_temp['mailbox']['highestmodseq']) &&
                ($this->_temp['mailbox']['highestmodseq'] === -1)) {
                $this->close();
            }
        }

        /* If FLAGS/PERMFLAGS/UIDNOTSTICKY/FIRSTUNSEEN are needed, we must do
         * a SELECT/EXAMINE to get this information (data will be caught in
         * the code below). */
        if (($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) ||
            ($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY)) {
            $this->openMailbox($mailbox);
        }

        foreach ($items as $key => $val) {
            if ($key & $flags) {
                if ($mailbox->equals($this->_selected)) {
                    if (isset($this->_temp['mailbox'][$val])) {
                        $data[$val] = $this->_temp['mailbox'][$val];
                    } elseif ($key == Horde_Imap_Client::STATUS_UIDNEXT) {
                        /* UIDNEXT is not strictly required on mailbox open.
                         * See RFC 3501 [6.3.1]. */
                        $data[$val] = 0;

                        if (($flags & Horde_Imap_Client::STATUS_UIDNEXT_FORCE) &&
                            !empty($this->_temp['mailbox']['messages'])) {
                            $squery = new Horde_Imap_Client_Search_Query();
                            $squery->ids($this->getIdsOb(Horde_Imap_Client_Ids::LARGEST));
                            $s_res = $this->search($this->_selected, $squery);
                            $data[$val] = $s_res['match']->ids[0] + 1;
                        }
                    } elseif ($key == Horde_Imap_Client::STATUS_UIDNOTSTICKY) {
                        /* In the absence of uidnotsticky information, or
                         * if UIDPLUS is not supported, we assume the UIDs
                         * are sticky. */
                        $data[$val] = false;
                    } elseif ($key == Horde_Imap_Client::STATUS_PERMFLAGS) {
                        /* If PERMFLAGS is not returned by server, must assume
                         * that all flags can be changed permanently. See
                         * RFC 3501 [6.3.1]. */
                        $data[$val] = isset($this->_temp['mailbox'][$items[Horde_Imap_Client::STATUS_FLAGS]])
                            ? $this->_temp['mailbox'][$items[Horde_Imap_Client::STATUS_FLAGS]]
                            : array();
                        $data[$val][] = "\\*";
                    } elseif (in_array($key, array(Horde_Imap_Client::STATUS_FIRSTUNSEEN, Horde_Imap_Client::STATUS_UNSEEN))) {
                        /* If we already know there are no messages in the
                         * current mailbox, we know there is no firstunseen
                         * and unseen info also. */
                        if (empty($this->_temp['mailbox']['messages'])) {
                            $data[$val] = ($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? null : 0;
                        } else {
                            /* RFC 3501 [6.3.1] - FIRSTUNSEEN information is
                             * not mandatory. If missing in EXAMINE/SELECT
                             * results, we need to do a search. An UNSEEN
                             * count also requires a search. */
                            if (is_null($search)) {
                                $search_query = new Horde_Imap_Client_Search_Query();
                                $search_query->flag(Horde_Imap_Client::FLAG_SEEN, false);
                                $search = $this->search($mailbox, $search_query, array('results' => array(($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? Horde_Imap_Client::SEARCH_RESULTS_MIN : Horde_Imap_Client::SEARCH_RESULTS_COUNT), 'sequence' => true));
                            }

                            $data[$val] = $search[($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? 'min' : 'count'];
                        }
                    }
                } else {
                    $query->add(strtoupper($val));
                }
            }
        }

        if (!count($query)) {
            return $data;
        }

        $cmd = $this->_clientCommand(array(
            'STATUS',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
            $query
        ));

        $this->_sendLine($cmd);

        return $this->_temp['status'][strval($mailbox)];
    }

    /**
     * Parse a STATUS response (RFC 3501 [7.2.4], RFC 4551 [3.6])
     *
     * @param Horde_Imap_Client_Tokenize $data  Token data
     */
    protected function _parseStatus(Horde_Imap_Client_Tokenize $data)
    {
        // Mailbox name is in UTF7-IMAP
        $mbox = strval(Horde_Imap_Client_Mailbox::get($data->current(), true));

        $this->_temp['status'][$mbox] = array();

        $s_data = $data->next();
        $k = $s_data->rewind();

        do {
            $this->_temp['status'][$mbox][strtolower($k)] = $s_data->next();
        } while (($k = $s_data->next()) !== false);
    }

    /**
     */
    protected function _append(Horde_Imap_Client_Mailbox $mailbox, $data,
                               $options)
    {
        // Check for MULTIAPPEND extension (RFC 3502)
        if ((count($data) > 1) && !$this->queryCapability('MULTIAPPEND')) {
            $result = $this->getIdsOb();
            foreach (array_keys($data) as $key) {
                $res = $this->_append($mailbox, array($data[$key]), $options);
                if (($res === true) || ($result === true)) {
                    $result = true;
                } else {
                    $result->add($res);
                }
            }
            return $result;
        }

        // If the mailbox is currently selected read-only, we need to close
        // because some IMAP implementations won't allow an append.
        $this->close();

        // Check for CATENATE extension (RFC 4469)
        $catenate = $this->queryCapability('CATENATE');

        $t = &$this->_temp;
        $t['appendsize'] = 0;
        $t['appenduid'] = $this->getIdsOb();
        $t['trycreate'] = null;
        $t['uidplusmbox'] = $mailbox;

        $cmd = $this->_clientCommand(array(
            'APPEND',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));

        foreach (array_keys($data) as $key) {
            if (!empty($data[$key]['flags'])) {
                $tmp = new Horde_Imap_Client_Data_Format_List();
                foreach ($data[$key]['flags'] as $val) {
                    /* Ignore recent flag. RFC 3501 [9]: flag definition */
                    if (strcasecmp($val, Horde_Imap_Client::FLAG_RECENT) !== 0) {
                        $tmp->add($val);
                    }
                }
                $cmd->add($tmp);
            }

            if (!empty($data[$key]['internaldate'])) {
                $cmd->add(new Horde_Imap_Client_Data_Format_DateTime($data[$key]['internaldate']));
            }

            if (is_array($data[$key]['data'])) {
                if ($catenate) {
                    $cmd->add('CATENATE');
                    $tmp = new Horde_Imap_Client_Data_Format_List();
                } else {
                    $data_stream = new Horde_Stream_Temp();
                }

                reset($data[$key]['data']);
                while (list(,$v) = each($data[$key]['data'])) {
                    switch ($v['t']) {
                    case 'text':
                        $text_data = $this->_appendData($v['v']);

                        if ($catenate) {
                            $text_str = new Horde_Imap_Client_Data_Format_String($text_data);
                            $text_str->forceLiteral();

                            $tmp->add(array(
                                'TEXT',
                                $text_str
                            ));
                        } else {
                            $data_stream->add($text_data);
                        }
                        break;

                    case 'url':
                        if ($catenate) {
                            $tmp->add(array(
                                'URL',
                                new Horde_Imap_Client_Data_Format_Astring($v['v'])
                            ));
                        } else {
                            $data_stream->add($this->_convertCatenateUrl($v['v']));
                        }
                        break;
                    }
                }

                if ($catenate) {
                    $cmd->add($tmp);
                } else {
                    rewind($data_stream->stream);
                    $text_data = new Horde_Imap_Client_Data_Format_String($data_stream);
                    $text_data->forceLiteral();
                    $cmd->add($text_data);
                }
            } else {
                $cmd->add(new Horde_Imap_Client_Data_Format_String($this->_appendData($data[$key]['data'])));
            }
        }

        try {
            $this->_sendLine($cmd, array(
                /* Although it is normally more efficient to use LITERAL+,
                 * disable here if our payload is over 0.5 MB because it
                 * allows the server to throw error before we potentially push
                 * a lot of data to server that would otherwise be ignored
                 * (see RFC 4549 [4.2.2.3]). */
                'noliteralplus' => ($this->_temp['appendsize'] > 524288)
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            switch ($e->getCode()) {
            case $e::CATENATE_BADURL:
            case $e::CATENATE_TOOBIG:
                /* Cyrus 2.4 (at least as of .14) has a broken CATENATE (see
                 * Bug #11111). Regardless, if CATENATE is broken, we can try
                 * to fallback to APPEND. */
                $cap = $this->capability();
                unset($cap['CATENATE']);
                $this->_setInit('capability', $cap);

                return $this->_append($mailbox, $data, $options);
            }

            if (!empty($options['create']) && $this->_temp['trycreate']) {
                $this->createMailbox($mailbox);
                unset($options['create']);
                return $this->_append($mailbox, $data, $options);
            }

            throw $e;
        }

        /* If we reach this point and have data in $_temp['appenduid'],
         * UIDPLUS (RFC 4315) has done the dirty work for us. */
        return count($t['appenduid'])
            ? $t['appenduid']
            : true;
    }

    /**
     * Prepares append message data for insertion into the IMAP command
     * string.
     *
     * @param mixed $data  Either a resource or a string.
     *
     * @param Horde_Stream  A stream containing the data.
     */
    protected function _appendData($data)
    {
        $stream = new Horde_Stream_Temp();
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
        $res = stream_filter_append($stream->stream, 'horde_eol', STREAM_FILTER_WRITE);

        if (is_resource($data)) {
            rewind($data);
        }
        $stream->add($data, true);

        $this->_temp['appendsize'] += $stream->length();

        stream_filter_remove($res);

        return $stream;
    }

    /**
     * Converts a CATENATE URL to stream data.
     *
     * @param string $url  The CATENATE URL.
     *
     * @return Horde_Stream  A stream containing the data.
     */
    protected function _convertCatenateUrl($url)
    {
        $e = $part = null;
        $url = new Horde_Imap_Client_Url($url);

        if (!is_null($url->mailbox) && !is_null($url->uid)) {
            try {
                $status_res = is_null($url->uidvalidity)
                    ? null
                    : $this->status($url->mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);

                if (is_null($status_res) ||
                    ($status_res['uidvalidity'] == $url->uidvalidity)) {
                    if (!isset($this->_temp['catenate_ob'])) {
                        $this->_temp['catenate_ob'] = new Horde_Imap_Client_Socket_Catenate($this);
                    }
                    $part = $this->_temp['catenate_ob']->fetchFromUrl($url);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        if (is_null($part)) {
            $message = 'Bad IMAP URL given in CATENATE data: ' . strval($url);
            if ($e) {
                $message .= ' ' . $e->getMessage();
            }

            throw new InvalidArgumentException($message);
        }

        return $this->_appendData($part);
    }

    /**
     */
    protected function _check()
    {
        // CHECK returns no untagged information (RFC 3501 [6.4.1])
        $this->_sendLine($this->_clientCommand('CHECK'));
    }

    /**
     */
    protected function _close($options)
    {
        if (empty($options['expunge'])) {
            if ($this->queryCapability('UNSELECT')) {
                // RFC 3691 defines 'UNSELECT' for precisely this purpose
                $this->_sendLine($this->_clientCommand('UNSELECT'));
            } else {
                // RFC 3501 [6.4.2]: to close a mailbox without expunge,
                // select a non-existent mailbox. Selecting a null mailbox
                // should do the trick.
                try {
                    $cmd = $this->_clientCommand('SELECT');
                    $cmd->add('');
                    $this->_sendLine($cmd);
                } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                    // Ignore error; it is expected.
                }
            }
        } else {
            // If caching, we need to know the UIDs being deleted, so call
            // expunge() before calling close().
            if ($this->_initCache(true)) {
                $this->expunge($this->_selected);
            }

            // CLOSE returns no untagged information (RFC 3501 [6.4.2])
            $this->_sendLine($this->_clientCommand('CLOSE'));

            /* Ignore HIGHESTMODSEQ information (RFC 5162 [3.4]) since the
             * expunge() call would have already caught it. */
        }

        // Need to clear status cache since we are no longer in mailbox.
        $this->_temp['mailbox'] = array();
    }

    /**
     */
    protected function _expunge($options)
    {
        $unflag = array();
        $mailbox = clone($this->_selected);
        $s_res = null;
        $uidplus = $this->queryCapability('UIDPLUS');
        $use_cache = $this->_initCache(true);

        if ($options['ids']->all) {
            $uid_string = strval($options['ids']);
        } elseif ($uidplus) {
            /* UID EXPUNGE command needs UIDs. */
            if ($options['ids']->sequence) {
                $results = array(Horde_Imap_Client::SEARCH_RESULTS_MATCH);
                if ($this->queryCapability('SEARCHRES')) {
                    $results[] = Horde_Imap_Client::SEARCH_RESULTS_SAVE;
                }
                $s_res = $this->search($mailbox, null, array(
                    'results' => $results
                ));
                $uid_string = (in_array(Horde_Imap_Client::SEARCH_RESULTS_SAVE, $results) && !empty($s_res['save']))
                    ? '$'
                    : strval($s_res['match']);
            } else {
                $uid_string = strval($options['ids']);
            }
        } else {
            /* Without UIDPLUS, need to temporarily unflag all messages marked
             * as deleted but not a part of requested IDs to delete. Use NOT
             * searches to accomplish this goal. */
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag(Horde_Imap_Client::FLAG_DELETED, true);
            if ($options['ids']->search_res) {
                $search_query->previousSearch(true);
            } else {
                $search_query->ids($options['ids'], true);
            }

            $res = $this->search($mailbox, $search_query);

            $this->store($mailbox, array(
                'ids' => $res['match'],
                'remove' => array(Horde_Imap_Client::FLAG_DELETED)
            ));

            $unflag = $res['match'];
        }

        $list_msgs = !empty($options['list']);
        $tmp = &$this->_temp;
        $tmp['expunge'] = $tmp['vanished'] = array();

        /* We need to get sequence num -> UID lookup table if we are caching.
         * There is no guarantee that if we are using QRESYNC that we will get
         * VANISHED responses, so this is unfortunately necessary. */
        if (is_null($s_res) && ($list_msgs || $use_cache)) {
            $s_res = $uidplus
                ? $this->_getSeqUidLookup($options['ids'], true)
                : $this->_getSeqUidLookup($this->getIdsOb(Horde_Imap_Client_Ids::ALL, true));
        }

        /* Always use UID EXPUNGE if available. */
        if ($uidplus) {
            $cmd = $this->_clientCommand(array(
                'UID',
                'EXPUNGE',
                $uid_string
            ));
            $this->_sendLine($cmd);
        } elseif ($use_cache || $list_msgs) {
            $this->_sendLine($this->_clientCommand('EXPUNGE'));
        } else {
            /* This is faster than an EXPUNGE because the server will not
             * return untagged EXPUNGE responses. We can only do this if
             * we are not updating cache information. */
            $this->close(array('expunge' => true));
        }

        if (!empty($unflag)) {
            $this->store($mailbox, array(
                'add' => array(Horde_Imap_Client::FLAG_DELETED),
                'ids' => $unflag
            ));
        }

        if (!$use_cache && !$list_msgs) {
            return null;
        }

        $expunged = array();

        if (!empty($tmp['vanished'])) {
            $expunged = $tmp['vanished']->ids;
        } elseif (!empty($tmp['expunge'])) {
            $lookup = $s_res['lookup'];

            /* Expunge responses can come in any order. Thus, we need to
             * reindex anytime we have an index that appears equal to or
             * after a previously seen index. If an IMAP server is smart,
             * it will expunge in reverse order instead. */
            foreach ($tmp['expunge'] as &$val) {
                $found = false;
                $tmp2 = array();

                foreach (array_keys($lookup) as $i => $seq) {
                    if ($found) {
                        $tmp2[$seq - 1] = $lookup[$seq];
                    } elseif ($seq == $val) {
                        $expunged[] = $lookup[$seq];
                        $tmp2 = array_slice($lookup, 0, $i, true);
                        $found = true;
                    }
                }

                $lookup = $tmp2;
            }
        }

        if (empty($expunged)) {
            return null;
        }

        if ($use_cache) {
            $this->_deleteMsgs($mailbox, $expunged);
        }

        /* Update MODSEQ if active for mailbox (QRESYNC only; CONDSTORE
         * requires us to re-SELECT/EXAMINE the mailbox). */
        if (!empty($this->_temp['mailbox']['highestmodseq']) &&
            isset($this->_init['enabled']['QRESYNC'])) {
            $this->_updateMetaData($mailbox, array(
                self::CACHE_MODSEQ => $this->_temp['mailbox']['highestmodseq']
            ), isset($this->_temp['mailbox']['uidvalidity']) ? $this->_temp['mailbox']['uidvalidity'] : null);
        }

        return $list_msgs
            ? $this->getIdsOb($expunged, $options['ids']->sequence)
            : null;
    }

    /**
     * Parse an EXPUNGE response (RFC 3501 [7.4.1]).
     *
     * @param integer $seq  The message sequence number.
     */
    protected function _parseExpunge($seq)
    {
        $this->_temp['expunge'][] = $seq;

        /* Bug #9915: Decrement the message list here because some broken
         * IMAP servers will send an unneeded EXISTS response after the
         * EXPUNGE list is processed (see RFC 3501 [7.4.1]). */
        --$this->_temp['mailbox']['messages'];
        $this->_temp['mailbox']['lookup'] = array();

        if (!empty($this->_init['enabled']['CONDSTORE'])) {
            $this->_temp['modseqs'][] = -1;
        }
    }

    /**
     * Parse a VANISHED response (RFC 5162 [3.6]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The response data.
     */
    protected function _parseVanished(Horde_Imap_Client_Tokenize $data)
    {
        $val = $data->current();
        $vanished = array();

        /* There are two forms of VANISHED.  VANISHED (EARLIER) will be sent
         * in a FETCH (VANISHED) or SELECT/EXAMINE (QRESYNC) call.
         * If this is the case, we can go ahead and update the cache
         * immediately (we know we are caching or else QRESYNC would not be
         * enabled). HIGHESTMODSEQ information will be updated via the tagged
         * response. */
        if (is_object($val)) {
            if (strtoupper($val->rewind()) == 'EARLIER') {
                /* Caching is guaranteed to be active if we are using
                 * QRESYNC. */
                $vanished = $this->getIdsOb($data->next());
                $this->_deleteMsgs($this->_temp['mailbox']['name'], $vanished);
            }
        } else {
            /* The second form is just VANISHED. This is returned from an
             * EXPUNGE command and will be processed in _expunge(). */
            $vanished = $this->getIdsOb($val);
            $this->_temp['mailbox']['messages'] -= count($vanished);
            $this->_temp['mailbox']['lookup'] = array();
        }

        $this->_temp['vanished'] = $vanished;
    }

    /**
     * Search a mailbox.  This driver supports all IMAP4rev1 search criteria
     * as defined in RFC 3501.
     */
    protected function _search($query, $options)
    {
        /* RFC 4551 [3.1] - trying to do a MODSEQ SEARCH on a mailbox that
         * doesn't support it will return BAD. Catch that here and throw
         * an exception. */
        if (in_array('CONDSTORE', $options['_query']['exts']) &&
            empty($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                    Horde_Imap_Client_Exception::MBOXNOMODSEQ
                );
        }

        $cmd = $this->_clientCommand(empty($options['sequence']) ? 'UID' : null);

        $sort_criteria = array(
            Horde_Imap_Client::SORT_ARRIVAL => 'ARRIVAL',
            Horde_Imap_Client::SORT_CC => 'CC',
            Horde_Imap_Client::SORT_DATE => 'DATE',
            Horde_Imap_Client::SORT_DISPLAYFROM => 'DISPLAYFROM',
            Horde_Imap_Client::SORT_DISPLAYTO => 'DISPLAYTO',
            Horde_Imap_Client::SORT_FROM => 'FROM',
            Horde_Imap_Client::SORT_REVERSE => 'REVERSE',
            Horde_Imap_Client::SORT_RELEVANCY => 'RELEVANCY',
            // This is a bogus entry to allow the sort options check to
            // correctly work below.
            Horde_Imap_Client::SORT_SEQUENCE => 'SEQUENCE',
            Horde_Imap_Client::SORT_SIZE => 'SIZE',
            Horde_Imap_Client::SORT_SUBJECT => 'SUBJECT',
            Horde_Imap_Client::SORT_TO => 'TO'
        );

        $results_criteria = array(
            Horde_Imap_Client::SEARCH_RESULTS_COUNT => 'COUNT',
            Horde_Imap_Client::SEARCH_RESULTS_MATCH => 'ALL',
            Horde_Imap_Client::SEARCH_RESULTS_MAX => 'MAX',
            Horde_Imap_Client::SEARCH_RESULTS_MIN => 'MIN',
            Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY => 'RELEVANCY',
            Horde_Imap_Client::SEARCH_RESULTS_SAVE => 'SAVE'
        );

        // Check if the server supports sorting (RFC 5256).
        $esearch = $return_sort = $server_seq_sort = $server_sort = false;
        if (!empty($options['sort'])) {
            /* Make sure sort options are correct. If not, default to no
             * sort. */
            if (count(array_intersect($options['sort'], array_keys($sort_criteria))) === 0) {
                unset($options['sort']);
            } else {
                $return_sort = true;

                if ($server_sort = $this->queryCapability('SORT')) {
                    /* Make sure server supports DISPLAYFROM & DISPLAYTO. */
                    $server_sort =
                        !array_intersect($options['sort'], array(Horde_Imap_Client::SORT_DISPLAYFROM, Horde_Imap_Client::SORT_DISPLAYTO)) ||
                        (is_array($server_sort) &&
                         in_array('DISPLAY', $server_sort));
                }

                /* If doing a sequence sort, need to do this on the client
                 * side. */
                if ($server_sort &&
                    in_array(Horde_Imap_Client::SORT_SEQUENCE, $options['sort'])) {
                    $server_sort = false;

                    /* Optimization: If doing only a sequence sort, just do a
                     * simple search and sort UIDs/sequences on client side. */
                    switch (count($options['sort'])) {
                    case 1:
                        $server_seq_sort = true;
                        break;

                    case 2:
                        $server_seq_sort = (reset($options['sort']) == Horde_Imap_Client::SORT_REVERSE);
                        break;
                    }
                }
            }
        }

        $charset = is_null($options['_query']['charset'])
            ? 'US-ASCII'
            : $options['_query']['charset'];

        if ($server_sort) {
            $cmd->add('SORT');
            $results = array();

            // Use ESEARCH (RFC 4466) response if server supports.
            $esearch = false;

            // Check for ESORT capability (RFC 5267)
            if ($this->queryCapability('ESORT')) {
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val]) &&
                        ($val != Horde_Imap_Client::SEARCH_RESULTS_SAVE)) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $esearch = true;
            }

            // Add PARTIAL limiting (RFC 5267 [4.4])
            if ((!$esearch || !empty($options['partial'])) &&
                ($cap = $this->queryCapability('CONTEXT')) &&
                in_array('SORT', $cap)) {
                /* RFC 5267 indicates RFC 4466 ESEARCH support,
                 * notwithstanding RFC 4731 support. */
                $esearch = true;

                if (!empty($options['partial'])) {
                    /* Can't have both ALL and PARTIAL returns. */
                    $results = array_diff($results, array('ALL'));

                    $results[] = 'PARTIAL';
                    $results[] = strval($this->getIdsOb($options['partial']));
                }
            }

            if ($esearch && empty($this->_init['noesearch'])) {
                $cmd->add(array(
                    'RETURN',
                    new Horde_Imap_Client_Data_Format_List($results)
                ));
            }

            $tmp = new Horde_Imap_Client_Data_Format_List();
            foreach ($options['sort'] as $val) {
                if (isset($sort_criteria[$val])) {
                    $tmp->add($sort_criteria[$val]);
                }
            }
            $cmd->add($tmp);

            // Charset is mandatory for SORT (RFC 5256 [3]).
            $cmd->add($charset);
        } else {
            $esearch = false;
            $results = array();

            $cmd->add('SEARCH');

            // Check if the server supports ESEARCH (RFC 4731).
            if ($this->queryCapability('ESEARCH')) {
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val])) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $esearch = true;
            }

            // Add PARTIAL limiting (RFC 5267 [4.4]).
            if ((!$esearch || !empty($options['partial'])) &&
                ($cap = $this->queryCapability('CONTEXT')) &&
                in_array('SEARCH', $cap)) {
                /* RFC 5267 indicates RFC 4466 ESEARCH support,
                 * notwithstanding RFC 4731 support. */
                $esearch = true;

                if (!empty($options['partial'])) {
                    // Can't have both ALL and PARTIAL returns.
                    $results = array_diff($results, array('ALL'));

                    $results[] = 'PARTIAL';
                    $results[] = strval($this->getIdsOb($options['partial']));
                }
            }

            if ($esearch && empty($this->_init['noesearch'])) {
                // Always use ESEARCH if available because it returns results
                // in a more compact sequence-set list
                $cmd->add(array(
                    'RETURN',
                    new Horde_Imap_Client_Data_Format_List($results)
                ));
            }

            // Charset is optional for SEARCH (RFC 3501 [6.4.4]).
            if ($charset != 'US-ASCII') {
                $cmd->add(array(
                    'CHARSET',
                    $options['_query']['charset']
                ));
            }

            // SEARCHRES requires ESEARCH
            unset($this->_temp['searchnotsaved']);
        }

        $er = &$this->_temp['esearchresp'];
        $er = array();
        $sr = &$this->_temp['searchresp'];
        $sr = $this->getIdsOb(array(), !empty($options['sequence']));

        $cmd->add($options['_query']['query'], true);

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            if (($e instanceof Horde_Imap_Client_Exception_ServerResponse) &&
                ($e->status == Horde_Imap_Client_Interaction_Server::NO) &&
                ($charset != 'US-ASCII')) {
                /* RFC 3501 [6.4.4]: BADCHARSET response code is only a
                 * SHOULD return. If it doesn't exist, need to check for
                 * command status of 'NO'. List of supported charsets in
                 * the BADCHARSET response has already been parsed and stored
                 * at this point. */
                $s_charset = $this->_init['s_charset'];
                $s_charset[$charset] = false;
                $this->_setInit('s_charset', $s_charset);
                $e->setCode(Horde_Imap_Client_Exception::BADCHARSET);
            }

            if (empty($this->_temp['search_retry'])) {
                $this->_temp['search_retry'] = true;

                /* Bug #9842: Workaround broken Cyrus servers (as of
                 * 2.4.7). */
                if ($esearch && ($charset != 'US-ASCII')) {
                    $cap = $this->capability();
                    unset($cap['ESEARCH']);
                    $this->_setInit('capability', $cap);
                    $this->_setInit('noesearch', true);

                    try {
                        return $this->_search($query, $options);
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                /* Try to convert charset. */
                if (($e->getCode() == Horde_Imap_Client_Exception::BADCHARSET) &&
                    ($charset != 'US-ASCII')) {
                    foreach (array_merge(array_keys(array_filter($this->_init['s_charset'])), array('US-ASCII')) as $val) {
                        $this->_temp['search_retry'] = 1;
                        $new_query = clone($query);
                        try {
                            $new_query->charset($val);
                            $options['_query'] = $new_query->build($this->capability());
                            return $this->_search($new_query, $options);
                        } catch (Horde_Imap_Client_Exception $e) {}
                    }
                }

                unset($this->_temp['search_retry']);
            }

            throw $e;
        }

        if ($return_sort && !$server_sort) {
            if ($server_seq_sort) {
                $sr->sort();
                if (reset($options['sort']) == Horde_Imap_Client::SORT_REVERSE) {
                    $sr->reverse();
                }
            } else {
                if (!isset($this->_temp['clientsort'])) {
                    $this->_temp['clientsort'] = new Horde_Imap_Client_Socket_ClientSort($this);
                }
                $sr = $this->getIdsOb($this->_temp['clientsort']->clientSort($sr, $options), !empty($options['sequence']));
            }
        }

        $ret = array();
        foreach ($options['results'] as $val) {
            switch ($val) {
            case Horde_Imap_Client::SEARCH_RESULTS_COUNT:
                $ret['count'] = $esearch ? $er['count'] : count($sr);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MATCH:
                $ret['match'] = $sr;
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MAX:
                $ret['max'] = $esearch
                    ? (isset($er['max']) ? $er['max'] : null)
                    : (count($sr) ? max($sr->ids) : null);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                $ret['min'] = $esearch
                    ? (isset($er['min']) ? $er['min'] : null)
                    : (count($sr) ? min($sr->ids) : null);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY:
                $ret['relevancy'] = ($esearch && isset($er['relevancy'])) ? $er['relevancy'] : array();
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_SAVE:
                $ret['save'] = $esearch ? empty($this->_temp['searchnotsaved']) : false;
                break;
            }
        }

        // Add modseq data, if needed.
        if (!empty($er['modseq'])) {
            $ret['modseq'] = $er['modseq'];
        }

        unset($this->_temp['search_retry']);

        /* Check for EXPUNGEISSUED (RFC 2180 [4.3]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
        }

        return $ret;
    }

    /**
     * Parse a SEARCH/SORT response (RFC 3501 [7.2.5]; RFC 4466 [3];
     * RFC 5256 [4]; RFC 5267 [3]).
     *
     * @param array $data  A list of IDs (message sequence numbers or UIDs).
     */
    protected function _parseSearch($data)
    {
        /* More than one search response may be sent. */
        $this->_temp['searchresp']->add($data);
    }

    /**
     * Parse an ESEARCH response (RFC 4466 [2.6.2])
     * Format: (TAG "a567") UID COUNT 5 ALL 4:19,21,28
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseEsearch(Horde_Imap_Client_Tokenize $data)
    {
        // Ignore search correlator information
        if (is_object($current = $data->current())) {
            $current = $data->next();
        }

        // Ignore UID tag
        if (!is_null($current) && (strtoupper($current) == 'UID')) {
            $current = $data->next();
        }

        do {
            $val = $data->next();
            $tag = strtoupper($current);

            switch ($tag) {
            case 'ALL':
                $this->_parseSearch($val);
                break;

            case 'COUNT':
            case 'MAX':
            case 'MIN':
            case 'MODSEQ':
            case 'RELEVANCY':
                $this->_temp['esearchresp'][strtolower($tag)] = $val;
                break;

            case 'PARTIAL':
                // RFC 5267 [4.4]
                $partial = iterator_to_array($val);
                $this->_parseSearch(end($partial));
                break;
            }
        } while (($current = $data->next()) !== false);
    }

    /**
     */
    protected function _setComparator($comparator)
    {
        $cmd = $this->_clientCommand('COMPARATOR');
        foreach ($comparator as $val) {
            $cmd->add(new Horde_Imap_Client_Data_Format_Astring($val));
        }
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _getComparator()
    {
        $this->_sendLine($this->_clientCommand('COMPARATOR'));

        return isset($this->_temp['comparator'])
            ? $this->_temp['comparator']
            : null;
    }

    /**
     * Parse a COMPARATOR response (RFC 5255 [4.8])
     *
     * @param array $data  The server response.
     */
    protected function _parseComparator($data)
    {
        $this->_temp['comparator'] = $data;
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    protected function _thread($options)
    {
        $thread_criteria = array(
            Horde_Imap_Client::THREAD_ORDEREDSUBJECT => 'ORDEREDSUBJECT',
            Horde_Imap_Client::THREAD_REFERENCES => 'REFERENCES',
            Horde_Imap_Client::THREAD_REFS => 'REFS'
        );

        $tsort = (isset($options['criteria']))
            ? (is_string($options['criteria']) ? strtoupper($options['criteria']) : $thread_criteria[$options['criteria']])
            : 'ORDEREDSUBJECT';

        $cap = $this->queryCapability('THREAD');
        if (!$cap || !in_array($tsort, $cap)) {
            switch ($tsort) {
            case 'ORDEREDSUBJECT':
                if (empty($options['search'])) {
                    $ids = $this->getIdsOb(Horde_Imap_Client_Ids::ALL, !empty($options['sequence']));
                } else {
                    $search_res = $this->search($this->_selected, $options['search'], array('sequence' => !empty($options['sequence'])));
                    $ids = $search_res['match'];
                }

                /* Do client-side ORDEREDSUBJECT threading. */
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->envelope();
                $query->imapDate();

                $fetch_res = $this->fetch($this->_selected, $query, array(
                    'ids' => $ids
                ));

                if (!isset($this->_temp['clientsort'])) {
                    $this->_temp['clientsort'] = new Horde_Imap_Client_Socket_ClientSort($this);
                }
                return $this->_temp['clientsort']->threadOrderedSubject($fetch_res, empty($options['sequence']));

            case 'REFERENCES':
            case 'REFS':
                throw new Horde_Imap_Client_Exception_NoSupportExtension(
                    'THREAD',
                    sprintf('Server does not support "%s" thread sort.', $tsort)
                );
            }
        }

        $cmd = $this->_clientCommand(array(
            empty($options['sequence']) ? 'UID' : null,
            'THREAD',
            $tsort
        ));

        if (empty($options['search'])) {
            $cmd->add(array(
                'US-ASCII',
                'ALL'
            ));
        } else {
            $search_query = $options['search']->build();
            $cmd->add(is_null($search_query['charset']) ? 'US-ASCII' : $search_query['charset']);
            $cmd->add($search_query['query'], true);
        }

        $this->_sendLine($cmd);

        return new Horde_Imap_Client_Data_Thread($this->_temp['threadparse'], empty($options['sequence']) ? 'uid' : 'sequence');
    }

    /**
     * Parse a THREAD response (RFC 5256 [4]).
     *
     * @param Horde_Imap_Client_Tokenize $data  Thread data.
     */
    protected function _parseThread(Horde_Imap_Client_Tokenize $data)
    {
        $out = array();

        while (($curr = $data->current()) !== false) {
            $thread = array();
            $this->_parseThreadLevel($thread, $curr);
            $out[] = $thread;
            $data->next();
        }

        $this->_temp['threadparse'] = $out;
    }

    /**
     * Parse a level of a THREAD response (RFC 5256 [4]).
     *
     * @param array $thread                     Results.
     * @param Horde_Imap_Client_Tokenize $data  Thread data.
     * @param integer $level                    The current tree level.
     */
    protected function _parseThreadLevel(&$thread,
                                         Horde_Imap_Client_Tokenize $data,
                                         $level = 0)
    {
        $curr = $data->rewind();
        do {
            if (is_object($curr)) {
                $this->_parseThreadLevel($thread, $curr, $level);
            } else {
                $thread[$curr] = $level++;
            }
        } while (($curr = $data->next()) !== false);
    }

    /**
     */
    protected function _fetch(Horde_Imap_Client_Fetch_Results $results,
                              Horde_Imap_Client_Fetch_Query $query,
                              $options)
    {
        $t = &$this->_temp;
        $t['fetchcmd'] = array();
        $fetch = new Horde_Imap_Client_Data_Format_List();

        /* Build an IMAP4rev1 compliant FETCH query. We handle the following
         * criteria:
         *   BINARY[.PEEK][<section #>]<<partial>> (RFC 3516)
         *     see BODY[] response
         *   BINARY.SIZE[<section #>] (RFC 3516)
         *   BODY[.PEEK][<section>]<<partial>>
         *     <section> = HEADER, HEADER.FIELDS, HEADER.FIELDS.NOT, MIME,
         *                 TEXT, empty
         *     <<partial>> = 0.# (# of bytes)
         *   BODYSTRUCTURE
         *   ENVELOPE
         *   FLAGS
         *   INTERNALDATE
         *   MODSEQ (RFC 4551)
         *   RFC822.SIZE
         *   UID
         *
         * No need to support these (can be built from other queries):
         * ===========================================================
         *   ALL macro => (FLAGS INTERNALDATE RFC822.SIZE ENVELOPE)
         *   BODY => Use BODYSTRUCTURE instead
         *   FAST macro => (FLAGS INTERNALDATE RFC822.SIZE)
         *   FULL macro => (FLAGS INTERNALDATE RFC822.SIZE ENVELOPE BODY)
         *   RFC822 => BODY[]
         *   RFC822.HEADER => BODY[HEADER]
         *   RFC822.TEXT => BODY[TEXT]
         */

        foreach ($query as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_STRUCTURE:
                $fetch->add('BODYSTRUCTURE');
                break;

            case Horde_Imap_Client::FETCH_FULLMSG:
                if (empty($c_val['peek'])) {
                    $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READWRITE);
                }
                $fetch->add(
                    'BODY' .
                    (!empty($c_val['peek']) ? '.PEEK' : '') .
                    '[]' .
                    $this->_partialAtom($c_val)
                );
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
            case Horde_Imap_Client::FETCH_BODYTEXT:
            case Horde_Imap_Client::FETCH_MIMEHEADER:
            case Horde_Imap_Client::FETCH_BODYPART:
            case Horde_Imap_Client::FETCH_HEADERS:
                foreach ($c_val as $key => $val) {
                    $cmd = ($key == 0)
                        ? ''
                        : $key . '.';
                    $main_cmd = 'BODY';

                    switch ($type) {
                    case Horde_Imap_Client::FETCH_HEADERTEXT:
                        $cmd .= 'HEADER';
                        break;

                    case Horde_Imap_Client::FETCH_BODYTEXT:
                        $cmd .= 'TEXT';
                        break;

                    case Horde_Imap_Client::FETCH_MIMEHEADER:
                        $cmd .= 'MIME';
                        break;

                    case Horde_Imap_Client::FETCH_BODYPART:
                        // Remove the last dot from the string.
                        $cmd = substr($cmd, 0, -1);

                        if (!empty($val['decode']) &&
                            $this->queryCapability('BINARY')) {
                            $main_cmd = 'BINARY';
                        }
                        break;

                    case Horde_Imap_Client::FETCH_HEADERS:
                        $cmd .= 'HEADER.FIELDS';
                        if (!empty($val['notsearch'])) {
                            $cmd .= '.NOT';
                        }
                        $cmd .= ' (' . implode(' ', array_map('strtoupper', $val['headers'])) . ')';

                        // Maintain a command -> label lookup so we can put
                        // the results in the proper location.
                        $t['fetchcmd'][$cmd] = $key;
                    }

                    if (empty($val['peek'])) {
                        $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READWRITE);
                    }

                    $fetch->add(
                        $main_cmd .
                        (!empty($val['peek']) ? '.PEEK' : '') .
                        '[' . $cmd . ']' .
                        $this->_partialAtom($val)
                    );
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPARTSIZE:
                if ($this->queryCapability('BINARY')) {
                    foreach ($c_val as $val) {
                        $fetch->add('BINARY.SIZE[' . $key . ']');
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                $fetch->add('ENVELOPE');
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                $fetch->add('FLAGS');
                break;

            case Horde_Imap_Client::FETCH_IMAPDATE:
                $fetch->add('INTERNALDATE');
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                $fetch->add('RFC822.SIZE');
                break;

            case Horde_Imap_Client::FETCH_UID:
                /* A UID FETCH will always return UID information (RFC 3501
                 * [6.4.8]). Don't add to query as it just creates a longer
                 * FETCH command. */
                if ($options['ids']->sequence) {
                    $fetch->add('UID');
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                // Nothing we need to add to fetch request unless sequence
                // is the only criteria.
                if (count($query) == 1) {
                    $fetch->add('UID');
                }
                break;

            case Horde_Imap_Client::FETCH_MODSEQ:
                /* The 'changedsince' modifier implicitly adds the MODSEQ
                 * FETCH item (RFC 4551 [3.3.1]). Don't add to query as it
                 * just creates a longer FETCH command. */
                if (empty($options['changedsince'])) {
                    /* RFC 4551 [3.1] - trying to do a FETCH of MODSEQ on a
                     * mailbox that doesn't support it will return BAD. Catch
                     * that here and throw an exception. */
                    if (empty($this->_temp['mailbox']['highestmodseq'])) {
                        throw new Horde_Imap_Client_Exception(
                            Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                            Horde_Imap_Client_Exception::MBOXNOMODSEQ
                        );
                    }
                    $fetch->add('MODSEQ');
                }
                break;
            }
        }

        $cmd = $this->_clientCommand(array_filter(array(
            $options['ids']->sequence ? null : 'UID',
            'FETCH',
            strval($options['ids'])
        )));


        if (empty($options['changedsince'])) {
            $cmd->add($fetch);
        } else {
            if (empty($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                    Horde_Imap_Client_Exception::MBOXNOMODSEQ
                );
            }

            /* We might just want the list of UIDs changed since a given
             * modseq. In that case, we don't have any other FETCH attributes,
             * but RFC 3501 requires at least one attribute to be
             * specified. */
            $cmd->add(array(
                count($fetch)
                    ? $fetch
                    : new Horde_Imap_Client_Data_Format_List('UID'),
                new Horde_Imap_Client_Data_Format_List(array(
                    'CHANGEDSINCE',
                    new Horde_Imap_Client_Data_Format_Number($options['changedsince'])
                ))
            ));
        }

        try {
            $this->_sendLine($cmd, array(
                'fetch' => $results
            ));
        } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
            // A NO response, when coupled with a sequence FETCH, most likely
            // means that messages were expunged. RFC 2180 [4.1]
            if ($options['ids']->sequence &&
                ($e->status == Horde_Imap_Client_Interaction_Server::NO)) {
                $this->_temp['expungeissued'] = true;
            }
        }

        /* Check for EXPUNGEISSUED (RFC 2180 [4.1]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
        }
    }

    /**
     * Add a partial atom to an IMAP command based on the criteria options.
     *
     * @param array $opts  Criteria options.
     *
     * @return string  The partial atom.
     */
    protected function _partialAtom($opts)
    {
        if (!empty($opts['length'])) {
            return '<' . (empty($opts['start']) ? 0 : intval($opts['start'])) . '.' . intval($opts['length']) . '>';
        }

        return empty($opts['start'])
            ? ''
            : ('<' . intval($opts['start']) . '>');
    }

    /**
     * Parse a FETCH response (RFC 3501 [7.4.2]). A FETCH response may occur
     * due to a FETCH command, or due to a change in a message's state (i.e.
     * the flags change).
     *
     * @param integer $id                       The message sequence number.
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseFetch($id, Horde_Imap_Client_Tokenize $data)
    {
        $uid = null;

        /* At this point, we don't have access to the UID of the entry. Thus,
         * need to cache data locally until we reach the end. */
        $ob = new $this->_fetchDataClass();
        $ob->setSeq($id);

        $f_data = $data->current();
        $tag = $f_data->rewind();

        do {
            $tag = strtoupper($tag);

            switch ($tag) {
            case 'BODYSTRUCTURE':
                $structure = $this->_parseBodystructure($f_data->next());
                $structure->buildMimeIds();
                $ob->setStructure($structure);
                break;

            case 'ENVELOPE':
                $ob->setEnvelope($this->_parseEnvelope($f_data->next()));
                break;

            case 'FLAGS':
                $ob->setFlags(iterator_to_array($f_data->next()));
                break;

            case 'INTERNALDATE':
                $ob->setImapDate($f_data->next());
                break;

            case 'RFC822.SIZE':
                $ob->setSize($f_data->next());
                break;

            case 'UID':
                $uid = $f_data->next();
                $ob->setUid($uid);
                $this->_temp['mailbox']['lookup'][$id] = $uid;
                break;

            case 'MODSEQ':
                $modseq = reset(iterator_to_array($f_data->next()));

                $ob->setModSeq($modseq);

                /* Store MODSEQ value. It may be used as the highestmodseq
                 * once a tagged response is received (RFC 5162 [5]). */
                $this->_temp['modseqs'][] = $modseq;
                break;

            default:
                // Catch BODY[*]<#> responses
                if (strpos($tag, 'BODY[') === 0) {
                    // Remove the beginning 'BODY['
                    $tag = substr($tag, 5);

                    // BODY[HEADER.FIELDS] request
                    if (!empty($this->_temp['fetchcmd']) &&
                        (strpos($tag, 'HEADER.FIELDS') !== false)) {
                        // A HEADER.FIELDS entry will be tokenized thusly:
                        //   [0] => BODY[#.HEADER.FIELDS.NOT
                        //   [1] => Array
                        //     (
                        //       [0] => MESSAGE-ID
                        //     )
                        //   [2] => ]<0>
                        //   [3] => **Header search text**
                        $sig = $tag . ' (' . implode(' ', array_map('strtoupper', iterator_to_array($f_data->next()))) . ')';

                        // Ignore the trailing bracket
                        $f_data->next();

                        $ob->setHeaders($this->_temp['fetchcmd'][$sig], $f_data->next());
                    } else {
                        // Remove trailing bracket and octet start info
                        $tag = substr($tag, 0, strrpos($tag, ']'));

                        if (!strlen($tag)) {
                            // BODY[] request
                            if (!is_null($tmp = $f_data->next())) {
                                $ob->setFullMsg($tmp);
                            }
                        } elseif (is_numeric(substr($tag, -1))) {
                            // BODY[MIMEID] request
                            if (!is_null($tmp = $f_data->next())) {
                                $ob->setBodyPart($tag, $tmp);
                            }
                        } else {
                            // BODY[HEADER|TEXT|MIME] request
                            if (($last_dot = strrpos($tag, '.')) === false) {
                                $mime_id = 0;
                            } else {
                                $mime_id = substr($tag, 0, $last_dot);
                                $tag = substr($tag, $last_dot + 1);
                            }

                            if (!is_null($tmp = $f_data->next())) {
                                switch ($tag) {
                                case 'HEADER':
                                    $ob->setHeaderText($mime_id, $tmp);
                                    break;

                                case 'TEXT':
                                    $ob->setBodyText($mime_id, $tmp);
                                    break;

                                case 'MIME':
                                    $ob->setMimeHeader($mime_id, $tmp);
                                    break;
                                }
                            }
                        }
                    }
                } elseif (strpos($tag, 'BINARY[') === 0) {
                    // Catch BINARY[*]<#> responses
                    // Remove the beginning 'BINARY[' and the trailing bracket
                    // and octet start info
                    $tag = substr($tag, 7, strrpos($tag, ']') - 7);
                    $ob->setBodyPart($tag, $f_data->next(), empty($this->_temp['literal8']) ? '8bit' : 'binary');
                } elseif (strpos($tag, 'BINARY.SIZE[') === 0) {
                    // Catch BINARY.SIZE[*] responses
                    // Remove the beginning 'BINARY.SIZE[' and the trailing
                    // bracket and octet start info
                    $tag = substr($tag, 12, strrpos($tag, ']') - 12);
                    $ob->setBodyPartSize($tag, $f_data->next());
                }
                break;
            }

        } while (($tag = $f_data->next()) !== false);

        if (is_null($this->_temp['fetchresp'])) {
            $this->_temp['fetchresp'] = new Horde_Imap_Client_Fetch_Results($this->_fetchDataClass, is_null($uid) ? Horde_Imap_Client_Fetch_Results::SEQUENCE : Horde_Imap_Client_Fetch_Results::UID);
        }

        $this->_temp['fetchresp']->get(is_null($uid) ? $id : $uid)->merge($ob);
    }

    /**
     * Recursively parse BODYSTRUCTURE data from a FETCH return (see
     * RFC 3501 [7.4.2]).
     *
     * @param Horde_Imap_Client_Tokenize $data  Data returned from the server.
     *
     * @return array  The array of bodystructure information.
     */
    protected function _parseBodystructure(Horde_Imap_Client_Tokenize $data)
    {
        $ob = new Horde_Mime_Part();

        // If index 0 is an array, this is a multipart part.
        if (is_object($entry = $data->rewind())) {
            // Keep going through array values until we find a non-array.
            do {
                $ob->addPart($this->_parseBodystructure($entry));
            } while (is_object($entry = $data->next()));

            // The first string entry after an array entry gives us the
            // subpart type.
            $ob->setType('multipart/' . $entry);

            // After the subtype is further extension information. This
            // information MAY not appear for BODYSTRUCTURE requests.

            // This is parameter information.
            if (is_object($tmp = $data->next())) {
                foreach ($this->_parseStructureParams($tmp, 'content-type') as $key => $val) {
                    $ob->setContentTypeParameter($key, $val);
                }
            }
        } else {
            $ob->setType($entry . '/' . $data->next());

            if (is_object($tmp = $data->next())) {
                foreach ($this->_parseStructureParams($tmp, 'content-type') as $key => $val) {
                    $ob->setContentTypeParameter($key, $val);
                }
            }

            if (!is_null($tmp = $data->next())) {
                $ob->setContentId($tmp);
            }

            if (!is_null($tmp = $data->next())) {
                $ob->setDescription(Horde_Mime::decode($tmp));
            }

            if (!is_null($tmp = $data->next())) {
                $ob->setTransferEncoding($tmp);
            }

            $ob->setBytes($data->next());

            // If the type is 'message/rfc822' or 'text/*', several extra
            // fields are included
            switch ($ob->getPrimaryType()) {
            case 'message':
                if ($ob->getSubType() == 'rfc822') {
                    $data->next(); // Ignore: envelope
                    $ob->addPart($this->_parseBodystructure($data->next()));
                    $data->next(); // Ignore: lines
                }
                break;

            case 'text':
                $data->next(); // Ignore: lines
                break;
            }

            // After the subtype is further extension information. This
            // information MAY appear for BODYSTRUCTURE requests.

            $data->next(); // Ignore: MD5
        }

        // This is disposition information
        if (is_object($tmp = $data->next())) {
            $ob->setDisposition($tmp->rewind());

            foreach ($this->_parseStructureParams($tmp->next(), 'content-disposition') as $key => $val) {
                $ob->setDispositionParameter($key, $val);
            }
        }

        // This is language information. It is either a single value or a list
        // of values.
        if (($tmp = $data->next()) !== false) {
            $ob->setLanguage($tmp);
        }

        $data->next(); // Ignore: location (RFC 2557)

        return $ob;
    }

    /**
     * Helper function to parse a parameters-like tokenized array.
     *
     * @param mixed $data   Message data. Either a Horde_Imap_Client_Tokenize
     *                      object or null.
     * @param string $type  The header name.
     *
     * @return array  The parameter array.
     */
    protected function _parseStructureParams($data, $type)
    {
        $params = array();

        if (is_null($data)) {
            return $params;
        }

        $name = $data->rewind();
        do {
            $params[strtolower($name)] = $data->next();
        } while (($name = $data->next()) !== false);

        $ret = Horde_Mime::decodeParam($type, $params);

        return $ret['params'];
    }

    /**
     * Parse ENVELOPE data from a FETCH return (see RFC 3501 [7.4.2]).
     *
     * @param Horde_Imap_Client_Tokenize $data  Data returned from the server.
     *
     * @return Horde_Imap_Client_Data_Envelope  An envelope object.
     */
    protected function _parseEnvelope(Horde_Imap_Client_Tokenize $data)
    {
        // 'route', the 2nd element, is deprecated by RFC 2822.
        $addr_structure = array(
            0 => 'personal',
            2 => 'mailbox',
            3 => 'host'
        );
        $env_data = array(
            0 => 'date',
            1 => 'subject',
            2 => 'from',
            3 => 'sender',
            4 => 'reply_to',
            5 => 'to',
            6 => 'cc',
            7 => 'bcc',
            8 => 'in_reply_to',
            9 => 'message_id'
        );

        $ret = new Horde_Imap_Client_Data_Envelope();
        $env_addrs = $this->_params['envelope_addrs'];
        $env_str = $this->_params['envelope_string'];

        foreach ($data as $key => $val) {
            if (!isset($env_data[$key]) || is_null($val)) {
                continue;
            }

            if (is_string($val)) {
                // These entries are text fields.
                $ret->$env_data[$key] = substr($val, 0, $env_str);
            } else {
                // These entries are address structures.
                $addr_ob = new Horde_Mail_Rfc822_Address();
                $group = null;
                $tmp = new Horde_Mail_Rfc822_List();

                foreach ($val as $key2 => $val2) {
                    if ($key2 >= $env_addrs) {
                        $val->flushIterator(false);
                        break;
                    }

                    $a_val = iterator_to_array($val2);

                    // RFC 3501 [7.4.2]: Group entry when host is NIL.
                    // Group end when mailbox is NIL; otherwise, this is
                    // mailbox name.
                    if (is_null($a_val[3])) {
                        if (is_null($a_val[2])) {
                            $group = null;
                        } else {
                            $group = new Horde_Mail_Rfc822_Group($a_val[2]);
                            $tmp->add($group);
                        }
                    } else {
                        $addr = clone $addr_ob;

                        foreach ($addr_structure as $add_key => $add_val) {
                            if (!is_null($a_val[$add_key])) {
                                $addr->$add_val = $a_val[$add_key];
                            }
                        }

                        if ($group) {
                            $group->addresses->add($addr);
                        } else {
                            $tmp->add($addr);
                        }
                    }
                }

                $ret->$env_data[$key] = $tmp;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _vanished($modseq, Horde_Imap_Client_Ids $ids)
    {
        if (empty($this->_temp['mailbox']['highestmodseq'])) {
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                Horde_Imap_Client_Exception::MBOXNOMODSEQ
            );
        }

        $this->_temp['vanished'] = array();

        $cmd = $this->_clientCommand(array(
            'UID',
            'FETCH',
            strval($ids),
            'UID',
            new Horde_Imap_Client_Data_Format_List(array(
                'VANISHED',
                'CHANGEDSINCE',
                new Horde_Imap_Client_Data_Format_Number($modseq)
            ))
        ));

        $this->_sendLine($cmd);

        return $this->_temp['vanished'];
    }

    /**
     */
    protected function _store($options)
    {
        $cmd = $this->_clientCommand(array(
            empty($options['sequence']) ? 'UID' : null,
            'STORE',
            strval($options['ids'])
        ));

        if (!empty($this->_temp['mailbox']['highestmodseq'])) {
            if (empty($options['unchangedsince'])) {
                /* If CONDSTORE is enabled, we need to verify UNCHANGEDSINCE
                 * added to ensure we get MODSEQ updated information (need to
                 * call via status() since value may be -1). */
                $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_HIGHESTMODSEQ);
                $ucsince = $status['highestmodseq'];
            } else {
                $ucsince = intval($options['unchangedsince']);
            }

            if ($ucsince) {
                $cmd->add(new Horde_Imap_Client_Data_Format_List(array(
                    'UNCHANGEDSINCE',
                    new Horde_Imap_Client_Data_Format_Number($ucsince)
                )));
            }
        } elseif (!empty($options['unchangedsince'])) {
            /* RFC 4551 [3.1] - trying to do a UNCHANGEDSINCE STORE on a
             * mailbox that doesn't support it will return BAD. Catch that
             * here and throw an exception. */
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                Horde_Imap_Client_Exception::MBOXNOMODSEQ
            );
        }

        $this->_temp['modified'] = $this->getIdsOb();

        if (!empty($options['replace'])) {
            $cmd->add('FLAGS' . ($this->_debug ? '' : '.SILENT'));
            $cmd->add($options['replace']);

            try {
                $this->_sendLine($cmd);
            } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                // A NO response, when coupled with a sequence STORE and
                // non-SILENT behavior, most likely means that messages were
                // expunged. RFC 2180 [4.2]
                if (!empty($options['sequence']) &&
                    !$this->_debug &&
                    ($e->status == Horde_Imap_Client_Interaction_Server::NO)) {
                    $this->_temp['expungeissued'] = true;
                }
            }

            $this->_storeUpdateCache('replace', $options['replace']);
        } else {
            foreach (array('add' => '+', 'remove' => '-') as $k => $v) {
                if (!empty($options[$k])) {
                    $cmdtmp = clone $cmd;
                    $cmdtmp->add($v . 'FLAGS' . ($this->_debug ? '' : '.SILENT'));
                    $cmdtmp->add($options[$k]);

                    try {
                        $this->_sendLine($cmdtmp);
                    } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                        // A NO response, when coupled with a sequence STORE
                        // and non-SILENT behavior, most likely means that
                        // messages were expunged. RFC 2180 [4.2]
                        if (!empty($options['sequence']) &&
                            !$this->_debug &&
                            ($e->status == Horde_Imap_Client_Interaction_Server::NO)) {
                            $this->_temp['expungeissued'] = true;
                        }
                    }

                    $this->_storeUpdateCache($k, $options[$k]);
                }
            }
        }

        $ret = $this->_temp['modified'];

        /* Check for EXPUNGEISSUED (RFC 2180 [4.2]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
        }

        return $ret;
    }

    /**
     * Update the flags in the cache. Only update if STORE was successful and
     * flag information was not returned.
     */
    protected function _storeUpdateCache($type, $update_flags)
    {
        if (!isset($this->_init['enabled']['CONDSTORE']) ||
            empty($this->_temp['mailbox']['highestmodseq']) ||
            !count($this->_temp['fetchresp'])) {
            return;
        }

        $fr = $this->_temp['fetchresp'];
        $tocache = new Horde_Imap_Client_Fetch_Results();
        $uids = array();

        switch ($fr->key_type) {
        case $fr::SEQUENCE:
            $seq_res = $this->_getSeqUidLookup($this->getIdsOb($fr->ids(), true));
            break;

        case $fr::UID:
            $seq_res = null;
            break;
        }

        foreach ($fr as $key => $val) {
            if (!$val->exists(Horde_Imap_Client::FETCH_FLAGS)) {
                $uids[$key] = is_null($seq_res)
                    ? $key
                    : $seq_res['lookup'][$key];
            }
        }

        /* Get the list of flags from the cache. */
        switch ($type) {
        case 'add':
        case 'remove':
            /* Caching is guaranteed to be active if CONDSTORE is active. */
            $data = $this->_cache->get($this->_selected, array_values($uids), array('HICflags'), $this->_temp['mailbox']['uidvalidity']);

            foreach ($uids as $key => $uid) {
                $flags = isset($data[$uid]['HICflags'])
                    ? $data[$uid]['HICflags']
                    : array();
                if ($type == 'add') {
                    $flags = array_merge($flags, $update_flags);
                } else {
                    $flags = array_diff($flags, $update_flags);
                }

                $tocache[$uid] = $fr[$key];
                $tocache[$uid]->setFlags(array_keys(array_flip($flags)));
            }
            break;

        case 'update':
            foreach ($uids as $uid) {
                $tocache[$uid] = $fr[$key];
                $tocache[$uid]->setFlags($update_flags);
            }
            break;
        }

        if (count($tocache)) {
            $this->_updateCache($tocache, array(
                'fields' => array(
                    Horde_Imap_Client::FETCH_FLAGS
                )
            ));
        }
    }

    /**
     */
    protected function _copy(Horde_Imap_Client_Mailbox $dest, $options)
    {
        $this->_temp['copyuid'] = $this->_temp['copyuidvalid'] = $this->_temp['trycreate'] = null;
        $this->_temp['uidplusmbox'] = $dest;

        // COPY returns no untagged information (RFC 3501 [6.4.7])
        try {
            $cmd = $this->_clientCommand(array_filter(array(
                $options['ids']->sequence ? null : 'UID',
                'COPY',
                strval($options['ids']),
                new Horde_Imap_Client_Data_Format_Mailbox($dest)
            )));

            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            if (!empty($options['create']) && $this->_temp['trycreate']) {
                $this->createMailbox($dest);
                unset($options['create']);
                return $this->_copy($dest, $options);
            }
            throw $e;
        }

        /* UIDPLUS (RFC 4315) allows easy determination of the UID of the
         * copied messages. If UID not returned, then destination mailbox
         * does not support persistent UIDs.
         * Use UIDPLUS information to move cached data to new mailbox (see
         * RFC 4549 [4.2.2.1]). */
        if (!is_null($this->_temp['copyuid'])) {
            $this->_moveCache($this->_selected, $dest, $this->_temp['copyuid'], $this->_temp['copyuidvalid']);
        }

        // If moving, delete the old messages now.
        if (!empty($options['move'])) {
            $opts = array('ids' => $options['ids']);
            $this->store($this->_selected, array_merge(array(
                'add' => array(Horde_Imap_Client::FLAG_DELETED)
            ), $opts));
            $this->expunge($this->_selected, $opts);
        }

        return is_null($this->_temp['copyuid'])
            ? true
            : $this->_temp['copyuid'];
    }

    /**
     */
    protected function _setQuota(Horde_Imap_Client_Mailbox $root, $resources)
    {
        $limits = new Horde_Imap_Client_Data_Format_List();

        foreach ($resources as $key => $val) {
            $limits->add(array(
                strtoupper($key),
                new Horde_Imap_Client_Data_Format_Number($val)
            ));
        }

        $cmd = $this->_clientCommand(array(
            'SETQUOTA',
            new Horde_Imap_Client_Data_Format_Astring($root),
            $limits
        ));

        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _getQuota(Horde_Imap_Client_Mailbox $root)
    {
        $this->_temp['quotaresp'] = array();

        $cmd = $this->_clientCommand(array(
            'GETQUOTA',
            new Horde_Imap_Client_Data_Format_Astring($root)
        ));

        $this->_sendLine($cmd);

        return reset($this->_temp['quotaresp']);
    }

    /**
     * Parse a QUOTA response (RFC 2087 [5.1]).
     *
     * @param Horde_Imap_Client_Parse_Tokenize $data  The server response.
     */
    protected function _parseQuota(Horde_Imap_Client_Tokenize $data)
    {
        $c = &$this->_temp['quotaresp'];

        $root = $data->current();
        $c[$root] = array();

        $q_data = $data->next();
        $curr = $q_data->rewind();

        do {
            $c[$root][strtolower($curr)] = array(
                'usage' => $q_data->next(),
                'limit' => $q_data->next()
            );
        } while (($curr = $q_data->next()) !== false);
    }

    /**
     */
    protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_temp['quotaresp'] = array();

        $cmd = $this->_clientCommand(array(
            'GETQUOTAROOT',
            new Horde_Imap_Client_Data_Format_Astring($mailbox)
        ));

        $this->_sendLine($cmd);

        return $this->_temp['quotaresp'];
    }

    /**
     */
    protected function _setACL(Horde_Imap_Client_Mailbox $mailbox, $identifier,
                               $options)
    {
        // SETACL returns no untagged information (RFC 4314 [3.1]).
        $cmd = $this->_clientCommand(array(
            'SETACL',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
            new Horde_Imap_Client_Data_Format_Astring($identifier),
            new Horde_Imap_Client_Data_Format_Astring($options['rights'])
        ));

        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _deleteACL(Horde_Imap_Client_Mailbox $mailbox, $identifier)
    {
        // DELETEACL returns no untagged information (RFC 4314 [3.2]).
        $cmd = $this->_clientCommand(array(
            'DELETEACL',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
            new Horde_Imap_Client_Data_Format_Astring($identifier)
        ));

        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _getACL(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_temp['getacl'] = array();

        $cmd = $this->_clientCommand(array(
            'GETACL',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));

        $this->_sendLine($cmd);

        return $this->_temp['getacl'];
    }

    /**
     * Parse an ACL response (RFC 4314 [3.6]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseACL(Horde_Imap_Client_Tokenize $data)
    {
        $acl = &$this->_temp['getacl'];

        // Ignore mailbox argument -> index 1
        $curr = $data->next();

        do {
            $acl[$curr] = ($curr[0] == '-')
                ? new Horde_Imap_Client_Data_AclNegative($data->next())
                : new Horde_Imap_Client_Data_Acl($data->next());
        } while (($curr = $data->next()) !== false);
    }

    /**
     */
    protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                      $identifier)
    {
        unset($this->_temp['listaclrights']);

        $cmd = $this->_clientCommand(array(
            'LISTRIGHTS',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
            new Horde_Imap_Client_Data_Format_Astring($identifier)
        ));

        $this->_sendLine($cmd);

        return isset($this->_temp['listaclrights'])
            ? $this->_temp['listaclrights']
            : new Horde_Imap_Client_Data_AclRights();
    }

    /**
     * Parse a LISTRIGHTS response (RFC 4314 [3.7]).
     *
     * @param Horde_Imap_Client_Tokenzie $data  The server response.
     */
    protected function _parseListRights(Horde_Imap_Client_Tokenize $data)
    {
        // Ignore mailbox and identifier arguments
        $data->next();
        $required = str_split($data->next());
        $data->next();

        $this->_temp['listaclrights'] = new Horde_Imap_Client_Data_AclRights(
            $required,
            $data->flushIterator()
        );
    }

    /**
     */
    protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox)
    {
        unset($this->_temp['myrights']);

        $cmd = $this->_clientCommand(array(
            'MYRIGHTS',
            new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
        ));

        $this->_sendLine($cmd);

        return isset($this->_temp['myrights'])
            ? $this->_temp['myrights']
            : new Horde_Imap_Client_Data_Acl();
    }

    /**
     * Parse a MYRIGHTS response (RFC 4314 [3.8]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     */
    protected function _parseMyRights(Horde_Imap_Client_Tokenize $data)
    {
        // Ignore 1st token (mailbox name)
        $this->_temp['myrights'] = new Horde_Imap_Client_Data_Acl($data->next());
    }

    /**
     */
    protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                    $entries, $options)
    {
        $this->_temp['metadata'] = array();

        if ($this->queryCapability('METADATA') ||
            ((strlen($mailbox) == 0) &&
             $this->queryCapability('METADATA-SERVER'))) {
            $cmd_options = new Horde_Imap_Client_Data_Format_List();

            if (!empty($options['maxsize'])) {
                $cmd_options->add(array(
                    'MAXSIZE',
                    new Horde_Imap_Client_Data_Format_Number($options['maxsize'])
                ));
            }
            if (!empty($options['depth'])) {
                $cmd_options->add(array(
                    'DEPTH',
                    new Horde_Imap_Client_Data_Format_Number($options['depth'])
                ));
            }

            $queries = new Horde_Imap_Client_Data_Format_List();
            foreach ($entries as $md_entry) {
                $queries->add(new Horde_Imap_Client_Data_Format_Astring($md_entry));
            }

            $cmd = $this->_clientCommand(array(
                'GETMETADATA',
                new Horde_Imap_Client_Data_Format_Mailbox($mailbox)
            ));
            if (count($cmd_options)) {
                $cmd->add($cmd_options);
            }
            $cmd->add($queries);

            $this->_sendLine($cmd);

            return $this->_temp['metadata'];
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('METADATA');
        }

        $queries = array();
        foreach ($entries as $md_entry) {
            list($entry, $type) = $this->_getAnnotateMoreEntry($md_entry);

            if (!isset($queries[$type])) {
                $queries[$type] = new Horde_Imap_Client_Data_Format_List();
            }
            $queries[$type]->add(new Horde_Imap_Client_Data_Format_String($entry));
        }

        $result = array();
        foreach ($queries as $key => $val) {
            // TODO: Honor maxsize and depth options.
            $cmd = $this->_clientCommand(array(
                'GETANNOTATION',
                new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
                $val,
                new Horde_Imap_Client_Data_Format_String($key)
            ));

            $this->_sendLine($cmd);

            $result = array_merge($result, $this->_temp['metadata']);
        }

        return $result;
    }

    /**
     * Split a name for the METADATA extension into the correct syntax for the
     * older ANNOTATEMORE version.
     *
     * @param string $name  A name for a metadata entry.
     *
     * @return array  A list of two elements: The entry name and the value
     *                type.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getAnnotateMoreEntry($name)
    {
        if (substr($name, 0, 7) == '/shared') {
            return array(substr($name, 7), 'value.shared');
        } else if (substr($name, 0, 8) == '/private') {
            return array(substr($name, 8), 'value.priv');
        }

        throw new Horde_Imap_Client_Exception(
            sprintf(Horde_Imap_Client_Translation::t("Invalid METADATA entry: \"%s\"."), $name),
            Horde_Imap_Client_Exception::METADATA_INVALID
        );
    }

    /**
     */
    protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox, $data)
    {
        if ($this->queryCapability('METADATA') ||
            ((strlen($mailbox) == 0) &&
             $this->queryCapability('METADATA-SERVER'))) {
            $data_elts = new Horde_Imap_Client_Data_Format_List();

            foreach ($data as $key => $value) {
                $data_elts->add(array(
                    new Horde_Imap_Client_Data_Format_Astring($key),
                    new Horde_Imap_Client_Data_Format_Nstring($value)
                ));
            }

            $cmd = $this->_clientCommand(array(
                'SETMETADATA',
                new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
                $data_elts
            ));

            $this->_sendLine($cmd);

            return;
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('METADATA');
        }

        foreach ($data as $md_entry => $value) {
            list($entry, $type) = $this->_getAnnotateMoreEntry($md_entry);

            $cmd = $this->_clientCommand(array(
                'SETANNOTATION',
                new Horde_Imap_Client_Data_Format_Mailbox($mailbox),
                new Horde_Imap_Client_Data_Format_String($entry),
                new Horde_Imap_Client_Data_Format_List(array(
                    new Horde_Imap_Client_Data_Format_String($type),
                    new Horde_Imap_Client_Data_Format_Nstring($value)
                ))
            ));

            $this->_sendLine($cmd);
        }
    }

    /**
     * Parse a METADATA response (RFC 5464 [4.4]).
     *
     * @param Horde_Imap_Client_Tokenize $data  The server response.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseMetadata(Horde_Imap_Client_Tokenize $data)
    {
        switch ($data->current()) {
        case 'ANNOTATION':
            $mbox = $data->next();

            // Ignore unsolicited responses.
            if (is_object($entry = $data->next())) {
                break;
            }

            $m_data = $data->next();
            $type = $m_data->rewind();

            do {
                switch ($type) {
                case 'value.priv':
                    $this->_temp['metadata'][$mbox]['/private' . $entry] = $m_data->next();
                    break;

                case 'value.shared':
                    $this->_temp['metadata'][$mbox]['/shared' . $entry] = $m_data->next();
                    break;

                default:
                    throw new Horde_Imap_Client_Exception(
                        sprintf(Horde_Imap_Client_Translation::t("Invalid METADATA value type \"%s\"."), $type),
                        Horde_Imap_Client_Exception::METADATA_INVALID
                    );
                }
            } while (($type = $data->next()) !== false);
            break;

        case 'METADATA':
            $mbox = $data->next();

            // Ignore unsolicited responses.
            if (!is_object($m_data = $data->next())) {
                break;
            }

            $entry = $m_data->rewind();

            do {
                $this->_temp['metadata'][$mbox][$entry] = $m_data->next();
            } while (($entry = $m_data->next()) !== false);
            break;
        }
    }

    /* Overriden methods. */

    /**
     */
    protected function _getSeqUidLookup(Horde_Imap_Client_Ids $ids,
                                        $reverse = false)
    {
        $ob = array(
            'lookup' => array(),
            'uids' => $this->getIdsOb()
        );

        if (!empty($this->_temp['mailbox']['lookup']) &&
            count($ids) &&
            ($ids->sequence || $reverse)) {
            $need = $this->getIdsOb(null, $ids->sequence);
            $t = $this->_temp['mailbox']['lookup'];

            foreach ($ids as $val) {
                if ($ids->sequence) {
                    if (isset($t[$val])) {
                        $ob['lookup'][$val] = $t[$val];
                        $ob['uids']->add($t[$val]);
                    } else {
                        $need->add($val);
                    }
                } else {
                    if (($key = array_search($val, $t)) !== false) {
                        $ob['lookup'][$key] = $val;
                        $ob['uids']->add($val);
                    } else {
                        $need->add($val);
                    }
                }
            }

            if (!count($need)) {
                return $ob;
            }

            $ids = $need;
        }

        $res = parent::_getSeqUidLookup($ids, $reverse);

        if (!empty($res['lookup'])) {
            $ob['lookup'] = $ob['lookup'] + $res['lookup'];
        }
        if (isset($res['uids'])) {
            $ob['uids']->add($res['uids']);
        }

        return $ob;
    }

    /**
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        /* Search caching requires MODSEQ, which may not be active for a
         * mailbox. */
        return empty($this->_temp['mailbox']['highestmodseq'])
            ? null
            : parent::_getSearchCache($type, $mailbox, $options);
    }

    /**
     */
    protected function _syncMailbox()
    {
        /* QRESYNC would have already synced the mailbox. */
        if (empty($this->_init['enabled']['QRESYNC'])) {
            parent::_syncMailbox();
        }
    }

    /* Internal functions. */

    /**
     * Perform a command on the IMAP server. A connection to the server must
     * have already been made.
     *
     * RFC 3501 allows the sending of multiple commands at once. For
     * simplicity of implementation, we will execute commands one at a time.
     * This allows us to easily determine data meant for a command while
     * scanning for untagged responses unilaterally sent by the server.
     * The only advantage of pipelining commands is to reduce the (small)
     * amount of overhead needed to send commands. Modern IMAP servers do not
     * meaningfully optimize response order internally, so that is not a
     * worthwhile reason to implement pipelining. Even the IMAP gurus admit
     * that pipelining is probably more trouble than it is worth.
     *
     * @param Horde_Imap_Client_Data_Format_List $data  The IMAP commands to
     *                                                  execute.
     * @param array $opts                               Additional options:
     *   - debug: (string) When debugging, send this string instead of the
     *            actual command/data sent.
     *            DEFAULT: Raw data output to debug stream.
     *   - fetch: (Horde_Imap_Client_Fetch_Results) Use this as the initial
     *            fetch results value.
     *            DEFAULT: Fetch result is empty
     *   - noliteralplus: (boolean) If true, don't use LITERAL+ extension.
     *                    DEFAULT: false
     *
     * @return Horde_Imap_Client_Interaction_Server  Server object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendLine(Horde_Imap_Client_Data_Format_List $data,
                                 array $opts = array())
    {
        /* Initialize internal data items at the beginning of a command. */
        if ($data instanceof Horde_Imap_Client_Interaction_Client) {
            $this->_temp['fetchresp'] = isset($opts['fetch'])
                ? $opts['fetch']
                : null;
            $this->_temp['lastcmd'] = $data;
            $this->_temp['modseqs'] = array();
        }

        $this->writeDebug('', Horde_Imap_Client::DEBUG_CLIENT);

        $this->_processSendList($data, $opts);

        if (!empty($opts['debug'])) {
            $this->writeDebug($opts['debug']);
        }

        $this->_writeStream('', array('eol' => true));

        while ($ob = $this->_getLine()) {
            switch (get_class($ob)) {
            case 'Horde_Imap_Client_Interaction_Server_Continuation':
            case 'Horde_Imap_Client_Interaction_Server_Tagged':
                break 2;
            }
        }

        return $ob;
    }

    /**
     * Process/send a command to the remote server.
     *
     * @param Horde_Imap_Client_Data_Format_List $data  Commands to send.
     * @param array $opts                               Options:
     *   - debug: (boolean) Whether debug info should be output.
     *   - noliteralplus: (boolean) If true, don't use LITERAL+ extension.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupport
     */
    protected function _processSendList($data, $opts)
    {
        $s_opts = array('nodebug' => !empty($opts['debug']));

        foreach ($data as $key => $val) {
            if ($key) {
                $this->_writeStream(' ', $s_opts);
            }

            if ($val instanceof Horde_Imap_Client_Data_Format_List) {
                $this->_writeStream('(', $s_opts);
                $this->_processSendList($val, $opts);
                $this->_writeStream(')', $s_opts);
            } elseif ($val instanceof Horde_Imap_Client_Data_Format_String) {
                if ($val->literal()) {
                    $literal = '';

                    /* RFC 3516 - Send literal8 if we have binary data.
                     * RFC 3516/4466 says we should be able to append binary
                     * data using literal8 "~{#} format", but it doesn't seem
                     * to work in all servers tried (UW-IMAP/Cyrus). However,
                     * there is no other way to append null data, so try
                     * anyway. */
                    if ($val->binary()) {
                        if (!$this->queryCapability('BINARY')) {
                            throw new Horde_Imap_Client_Exception_NoSupportExtension(
                                'BINARY',
                                'Cannot send binary data to server that does not support it.'
                            );
                        }
                        $binary = true;
                        $literal .= '~';
                    } else {
                        $binary = false;
                    }

                    $stream_ob = $val->getData();

                    $literal_len = $stream_ob->length();
                    $literal .= '{' . $literal_len;

                    /* RFC 2088 - If LITERAL+ is available, saves a roundtrip
                     * from the server. */
                    if (empty($opts['noliteralplus']) &&
                        $this->queryCapability('LITERAL+')) {
                        $this->_writeStream($literal . "+}", array_merge($s_opts, array(
                            'eol' => true
                        )));
                    } else {
                        $this->_writeStream($literal . "}", array_merge($s_opts, array(
                            'eol' => true
                        )));

                        $ob = $this->_getLine();
                        if (!($ob instanceof Horde_Imap_Client_Interaction_Server_Continuation)) {
                            $this->writeDebug("ERROR: Unexpected response from server while waiting for a continuation request.\n", Horde_Imap_Client::DEBUG_INFO);
                            $e = new Horde_Imap_Client_Exception(
                                Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                                Horde_Imap_Client_Exception::SERVER_READERROR
                            );
                            $e->details = strval($ob);
                            throw $e;
                        }
                    }

                    $this->_writeStream($stream_ob->stream, array_merge($s_opts, array(
                        'binary' => $binary,
                        'literal' => $literal_len
                    )));
                } else {
                    $this->_writeStream($val->escapeStream(), $s_opts);
                }
            } else {
                $this->_writeStream($val->escape(), $s_opts);
            }
        }
    }

    /**
     * Shortcut to creating a new IMAP client command object.
     *
     * @param mixed $cmd  The IMAP command(s) to add.
     */
    protected function _clientCommand($cmd = null)
    {
        $ob = new Horde_Imap_Client_Interaction_Client(++$this->_tag);
        if (!is_null($cmd)) {
            $ob->add($cmd);
        }
        return $ob;
    }

    /**
     * Writes data to the IMAP output stream and handles debug output.
     *
     * @param mixed $data  Either a string or stream resource.
     * @param array $opts  Additional options:
     *   - binary: (boolean) If true, the literal data is binary.
     *   - eol: (boolean) If true, output EOL.
     *   - literal: (integer) If set, the length of the literal data.
     *   - nodebug: (boolean) If true, don't output debug data.
     */
    protected function _writeStream($data, array $opts = array())
    {
        if (is_resource($data)) {
            rewind($data);
            while (!feof($data)) {
                fwrite($this->_stream, fread($data, 8192));
            }
        } else {
            fwrite($this->_stream, $data . (empty($opts['eol']) ? '' : "\r\n"));
        }

        if (!empty($opts['nodebug']) || !$this->_debug) {
            return;
        }

        if (isset($opts['literal']) &&
            empty($this->_params['debug_literal'])) {
            $this->writeDebug('[' . (empty($opts['binary']) ? 'LITERAL' : 'BINARY') . ' DATA: ' . $opts['literal'] . ' bytes]' . "\n", Horde_Imap_Client::DEBUG_CLIENT);
        } elseif (is_resource($data)) {
            rewind($data);
            while (!feof($data)) {
                $this->writeDebug(fread($data, 8192));
            }
        } else {
            $this->writeDebug($data . (empty($opts['eol']) ? '' : "\n"));
        }

        if (isset($opts['literal'])) {
            $this->writeDebug('', Horde_Imap_Client::DEBUG_CLIENT);
        }
    }

    /**
     * Gets data from the IMAP server stream and parses it.
     *
     * @return Horde_Imap_Client_Interaction_Server  Server object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLine()
    {
        $server = Horde_Imap_Client_Interaction_Server::create($this->_readStream());

        switch (get_class($server)) {
        case 'Horde_Imap_Client_Interaction_Server_Continuation':
            $this->_responseCode($server);
            break;

        case 'Horde_Imap_Client_Interaction_Server_Tagged':
            /* Update HIGHESTMODSEQ value. */
            if (!empty($this->_temp['modseqs'])) {
                $this->_temp['mailbox']['highestmodseq'] = max($this->_temp['modseqs']);
            }

            /* Update FETCH items. */
            if (!is_null($this->_temp['fetchresp'])) {
                $this->_updateCache($this->_temp['fetchresp']);
            }

            $this->_responseCode($server);
            break;

        case 'Horde_Imap_Client_Interaction_Server_Untagged':
            if (is_null($server->status)) {
                $this->_serverResponse($server);
            } else {
                $this->_responseCode($server);
            }
            break;
        }

        switch ($server->status) {
        case $server::BAD:
            /* A tagged BAD response indicates that the tagged command caused
             * the error. This information is unknown if untagged. (RFC 3501
             * [7.1.3]) */
            $cmd = ($server instanceof Horde_Imap_Client_Interaction_Server_Tagged)
                ? $this->_temp['lastcmd']->getCommand()
                : null;

            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("IMAP error reported by server."),
                0,
                $server->status,
                strval($server->token),
                $cmd
            );

        case $server::BYE:
            /* A BYE response received as part of a logout command should be
             * be treated like a regular command: a client MUST process the
             * entire command until logging out (RFC 3501 [3.4; 7.1.5]). */
            if (empty($this->_temp['logout'])) {
                $this->_temp['logout'] = true;
                $this->logout();
                $e = new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("IMAP Server closed the connection."),
                    Horde_Imap_Client_Exception::DISCONNECT
                );
                $e->details = strval($server);
                throw $e;
            }
            break;

        case $server::NO:
            /* An untagged NO response indicates a warning; ignore and assume
             * that it also included response text code that is handled
             * elsewhere. Throw exception if tagged; command handlers can
             * catch this if able to workaround this issue. (RFC 3501
             * [7.1.2]) */
            if ($server instanceof Horde_Imap_Client_Interaction_Server_Tagged) {
                throw new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("IMAP error reported by server."),
                    0,
                    $server->status,
                    strval($server->token),
                    $this->_temp['lastcmd']->getCommand()
                );
            }

        case $server::PREAUTH:
            /* The user was pre-authenticated. (RFC 3501 [7.1.4]) */
            $this->_temp['preauth'] = true;
            break;
        }

        return $server;
    }

    /**
     * Read data from incoming IMAP stream.
     *
     * @return Horde_Imap_Client_Tokenize  The tokenized data.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _readStream()
    {
        $got_data = false;
        $literal_len = null;
        $token = new Horde_Imap_Client_Tokenize();

        do {
            if (feof($this->_stream)) {
                $this->_temp['logout'] = true;
                $this->logout();
                $this->writeDebug("ERROR: Server closed the connection.\n", Horde_Imap_Client::DEBUG_INFO);
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mail server closed the connection unexpectedly."),
                    Horde_Imap_Client_Exception::DISCONNECT
                );
            }

            if (is_null($literal_len)) {
                $this->writeDebug('', Horde_Imap_Client::DEBUG_SERVER);

                while (($in = fgets($this->_stream)) !== false) {
                    $got_data = true;

                    if (substr($in, -1) == "\n") {
                        $in = rtrim($in);
                        if ($this->_debug) {
                            $this->writeDebug($in . "\n");
                        }
                        $token->add($in);
                        break;
                    }

                    if ($this->_debug) {
                        $this->writeDebug($in);
                    }
                    $token->add($in);
                }

                /* Check for literal data. */
                fseek($token->stream->stream, -1, SEEK_END);
                if ($token->stream->peek() == '}') {
                    $literal_data = $token->stream->getString($token->stream->search('{', true) - 1);
                    $literal_len = substr($literal_data, 2, -1);

                    if (is_numeric($literal_len)) {
                        if ($literal_len) {
                            $binary = ($literal_data[0] == '~');
                        } else {
                            // Skip 0-length literal data.
                            $literal_len = null;
                        }
                        continue;
                    }
                }
                break;
            }

            $debug_literal = ($this->_debug &&
                              !empty($this->_params['debug_literal']));
            $old_len = $literal_len;

            $this->writeDebug('', Horde_Imap_Client::DEBUG_SERVER);

            while ($literal_len && !feof($this->_stream)) {
                $in = fread($this->_stream, min($literal_len, 8192));
                $token->add($in);
                if ($debug_literal) {
                    $this->writeDebug($in);
                }

                $got_data = true;

                $in_len = strlen($in);
                if ($in_len > $literal_len) {
                    break;
                }
                $literal_len -= $in_len;
            }

            $literal_len = null;

            if (!$debug_literal) {
                $this->writeDebug('[' . ($binary ? 'BINARY' : 'LITERAL') . ' DATA: ' . $old_len . ' bytes]' . "\n");
            }
        } while (true);

        if (!$got_data) {
            $this->writeDebug("ERROR: IMAP read/timeout error.\n", Horde_Imap_Client::DEBUG_INFO);
            $this->logout();
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                Horde_Imap_Client_Exception::SERVER_READERROR
            );
        }

        return $token;
    }

    /**
     * Handle untagged server responses (see RFC 3501 [2.2.2]).
     *
     * @param Horde_Imap_Client_Interaction_Server $ob  Server response.
     */
    protected function _serverResponse(Horde_Imap_Client_Interaction_Server $ob)
    {
        $token = $ob->token;

        /* First, catch untagged responses where the name appears first on the
         * line. */
        switch (strtoupper($token->current())) {
        case 'CAPABILITY':
            $token->next();
            $this->_parseCapability($token->flushIterator());
            break;

        case 'LIST':
        case 'LSUB':
            $this->_parseList($token);
            break;

        case 'STATUS':
            // Parse a STATUS response (RFC 3501 [7.2.4]).
            $token->next();
            $this->_parseStatus($token);
            break;

        case 'SEARCH':
        case 'SORT':
            // Parse a SEARCH/SORT response (RFC 3501 [7.2.5] & RFC 5256 [4]).
            $token->next();
            $this->_parseSearch($token->flushIterator());
            break;

        case 'ESEARCH':
            // Parse an ESEARCH response (RFC 4466 [2.6.2]).
            $token->next();
            $this->_parseEsearch($token);
            break;

        case 'FLAGS':
            $this->_temp['mailbox']['flags'] = array_map('strtolower', iterator_to_array($token->next()));
            break;

        case 'QUOTA':
            $token->next();
            $this->_parseQuota($token);
            break;

        case 'QUOTAROOT':
            // Ignore this line - we can get this information from
            // the untagged QUOTA responses.
            break;

        case 'NAMESPACE':
            $token->next();
            $this->_parseNamespace($token);
            break;

        case 'THREAD':
            $token->next();
            $this->_parseThread($token);
            break;

        case 'ACL':
            $token->next();
            $this->_parseACL($token);
            break;

        case 'LISTRIGHTS':
            $token->next();
            $this->_parseListRights($token);
            break;

        case 'MYRIGHTS':
            $token->next();
            $this->_parseMyRights($token);
            break;

        case 'ID':
            // ID extension (RFC 2971)
            $token->next();
            $this->_parseID($token);
            break;

        case 'ENABLED':
            // ENABLE extension (RFC 5161)
            $token->next();
            $this->_parseEnabled($token);
            break;

        case 'LANGUAGE':
            // LANGUAGE extension (RFC 5255 [3.2])
            $token->next();
            $this->_parseLanguage($token);
            break;

        case 'COMPARATOR':
            // I18NLEVEL=2 extension (RFC 5255 [4.7])
            $token->next();
            $this->_parseComparator($token);
            break;

        case 'VANISHED':
            // QRESYNC extension (RFC 5162 [3.6])
            $token->next();
            $this->_parseVanished($token);
            break;

        case 'ANNOTATION':
        case 'METADATA':
            // Parse a ANNOTATEMORE/METADATA response.
            $this->_parseMetadata($token);
            break;

        default:
            // Next, look for responses where the keywords occur second.
            $first = $token->current();

            switch (strtoupper($token->next())) {
            case 'EXISTS':
                // EXISTS response - RFC 3501 [7.3.2]
                $this->_temp['mailbox']['messages'] = $first;
                if (!empty($this->_init['enabled']['CONDSTORE'])) {
                    $this->_temp['modseqs'][] = -1;
                }
                break;

            case 'RECENT':
                // RECENT response - RFC 3501 [7.3.1]
                $this->_temp['mailbox']['recent'] = $first;
                break;

            case 'EXPUNGE':
                // EXPUNGE response - RFC 3501 [7.4.1]
                $this->_parseExpunge($first);
                break;

            case 'FETCH':
                // FETCH response - RFC 3501 [7.4.2]
                $token->next();
                $this->_parseFetch($first, $token);
                break;
            }
            break;
        }
    }

    /**
     * Handle status responses (see RFC 3501 [7.1]).
     *
     * @param Horde_Imap_Client_Interaction_Server $ob  Server object.
     *
     * @throws Horde_Imap_Client_Exception_ServerResponse
     */
    protected function _responseCode(Horde_Imap_Client_Interaction_Server $ob)
    {
        if (is_null($ob->responseCode)) {
            return;
        }

        $rc = $ob->responseCode;

        switch ($rc->code) {
        case 'ALERT':
        // Defined by RFC 5530 [3] - Treat as an alert for now.
        case 'CONTACTADMIN':
            if (!isset($this->_temp['alerts'])) {
                $this->_temp['alerts'] = array();
            }
            $this->_temp['alerts'][] = strval($ob->token);
            break;

        case 'BADCHARSET':
            /* Store valid search charsets if returned by server. */
            $s_charset = array();
            foreach ($rc->data[0] as $val) {
                $s_charset[$val] = true;
            }

            if (!empty($s_charset)) {
                $this->_setInit('s_charset', array_merge(
                    $this->_init['s_charset'],
                    $s_charset
                ));
            }

            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Charset used in search query is not supported on the mail server."),
                Horde_Imap_Client_Exception::BADCHARSET,
                $ob->status,
                strval($ob->token)
            );

        case 'CAPABILITY':
            $this->_parseCapability($rc->data);
            break;

        case 'PARSE':
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server was unable to parse the contents of the mail message."),
                Horde_Imap_Client_Exception::PARSEERROR,
                $ob->status,
                strval($ob->token)
            );

        case 'READ-ONLY':
            $this->_mode = Horde_Imap_Client::OPEN_READONLY;
            break;

        case 'READ-WRITE':
            $this->_mode = Horde_Imap_Client::OPEN_READWRITE;
            break;

        case 'TRYCREATE':
            // RFC 3501 [7.1]
            $this->_temp['trycreate'] = true;
            break;

        case 'PERMANENTFLAGS':
            $this->_temp['mailbox']['permflags'] = array_map('strtolower', $rc->data[0]);
            break;

        case 'UIDNEXT':
        case 'UIDVALIDITY':
            $this->_temp['mailbox'][strtolower($rc->code)] = $rc->data[0];
            break;

        case 'UNSEEN':
            /* This is different from the STATUS UNSEEN response - this item,
             * if defined, returns the first UNSEEN message in the mailbox. */
            $this->_temp['mailbox']['firstunseen'] = $rc->data[0];
            break;

        case 'REFERRAL':
            // Defined by RFC 2221
            $this->_temp['referral'] = new Horde_Imap_Client_Url($rc->data[0]);
            break;

        case 'UNKNOWN-CTE':
            // Defined by RFC 3516
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server was unable to parse the contents of the mail message."),
                Horde_Imap_Client_Exception::UNKNOWNCTE,
                $ob->status,
                strval($ob->token)
            );

        case 'APPENDUID':
        case 'COPYUID':
            // Defined by RFC 4315
            // APPENDUID: [0] = UIDVALIDITY, [1] = UID(s)
            // COPYUID: [0] = UIDVALIDITY, [1] = UIDFROM, [2] = UIDTO
            if ($this->_temp['uidplusmbox']->equals($this->_selected) &&
                ($this->_temp['mailbox']['uidvalidity'] != $rc->data[0])) {
                $this->_temp['mailbox'] = array('uidvalidity' => $rc->data[0]);
                $this->_temp['searchnotsaved'] = true;
            }

            /* Check for cache expiration (see RFC 4549 [4.1]). */
            $this->_updateCache(new Horde_Imap_Client_Fetch_Results(), array(
                'mailbox' => $this->_temp['uidplusmbox'],
                'uidvalid' => $rc->data[0]
            ));

            if ($rc->code == 'APPENDUID') {
                $this->_temp['appenduid']->add($rc->data[1]);
            } else {
                $from = $this->getIdsOb($rc->data[1]);
                $to = $this->getIdsOb($rc->data[2]);
                $this->_temp['copyuid'] = array_combine($from->ids, $to->ids);
                $this->_temp['copyuidvalid'] = $rc->data[0];
            }
            break;

        case 'UIDNOTSTICKY':
            // Defined by RFC 4315 [3]
            $this->_temp['mailbox']['uidnotsticky'] = true;
            break;

        case 'BADURL':
            // Defined by RFC 4469 [4.1]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Could not save message on server."),
                Horde_Imap_Client_Exception::CATENATE_BADURL,
                $ob->status,
                strval($ob->token)
            );

        case 'TOOBIG':
            // Defined by RFC 4469 [4.2]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Could not save message data because it is too large."),
                Horde_Imap_Client_Exception::CATENATE_TOOBIG,
                $ob->status,
                strval($ob->token)
            );

        case 'HIGHESTMODSEQ':
            // Defined by RFC 4551 [3.1.1]
            $this->_temp['modseqs'][] = $rc->data[0];
            break;

        case 'NOMODSEQ':
            // Defined by RFC 4551 [3.1.2]
            $this->_temp['modseqs'][] = 0;
            break;

        case 'MODIFIED':
            // Defined by RFC 4551 [3.2]
            $this->_temp['modified']->add($rc->data[0]);
            break;

        case 'CLOSED':
            // Defined by RFC 5162 [3.7]
            if (isset($this->_temp['qresyncmbox'])) {
                $this->_temp['mailbox'] = array(
                    'name' => $this->_temp['qresyncmbox']
                );
                $this->_selected = $this->_temp['qresyncmbox'];
            }
            break;

        case 'NOTSAVED':
            // Defined by RFC 5182 [2.5]
            $this->_temp['searchnotsaved'] = true;
            break;

        case 'BADCOMPARATOR':
            // Defined by RFC 5255 [4.9]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The comparison algorithm was not recognized by the server."),
                Horde_Imap_Client_Exception::BADCOMPARATOR,
                $ob->status,
                strval($ob->token)
            );

        case 'METADATA':
            $md = $rc->data[0];

            switch ($md[0]) {
            case 'LONGENTRIES':
                // Defined by RFC 5464 [4.2.1]
                $this->_temp['metadata']['*longentries'] = intval($md[1]);
                break;

            case 'MAXSIZE':
                // Defined by RFC 5464 [4.3]
                throw new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because it is too large."),
                    Horde_Imap_Client_Exception::METADATA_MAXSIZE,
                    $ob->status,
                    intval($md[1])
                );

            case 'NOPRIVATE':
                // Defined by RFC 5464 [4.3]
                throw new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because the server does not support private annotations."),
                    Horde_Imap_Client_Exception::METADATA_NOPRIVATE,
                    $ob->status,
                    strval($ob->token)
                );

            case 'TOOMANY':
                // Defined by RFC 5464 [4.3]
                throw new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because the maximum number of annotations has been exceeded."),
                    Horde_Imap_Client_Exception::METADATA_TOOMANY,
                    $ob->status,
                    strval($ob->token)
                );
            }
            break;

        case 'UNAVAILABLE':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Remote server is temporarily unavailable."),
                Horde_Imap_Client_Exception::LOGIN_UNAVAILABLE
            );
            break;

        case 'AUTHENTICATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication failed."),
                Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
            );
            break;

        case 'AUTHORIZATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication was successful, but authorization failed."),
                Horde_Imap_Client_Exception::LOGIN_AUTHORIZATIONFAILED
            );
            break;

        case 'EXPIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication credentials have expired."),
                Horde_Imap_Client_Exception::LOGIN_EXPIRED
            );
            break;

        case 'PRIVACYREQUIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Operation failed due to a lack of a secure connection."),
                Horde_Imap_Client_Exception::LOGIN_PRIVACYREQUIRED
            );
            break;

        case 'NOPERM':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("You do not have adequate permissions to carry out this operation."),
                Horde_Imap_Client_Exception::NOPERM,
                $ob->status,
                strval($ob->token)
            );

        case 'INUSE':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("There was a temporary issue when attempting this operation. Please try again later."),
                Horde_Imap_Client_Exception::INUSE,
                $ob->status,
                strval($ob->token)
            );

        case 'EXPUNGEISSUED':
            // Defined by RFC 5530 [3]
            $this->_temp['expungeissued'] = true;
            break;

        case 'CORRUPTION':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server is reporting corrupt data in your mailbox."),
                Horde_Imap_Client_Exception::CORRUPTION,
                $ob->status,
                strval($ob->token)
            );

        case 'SERVERBUG':
        case 'CLIENTBUG':
        case 'CANNOT':
            // Defined by RFC 5530 [3]
            $this->writeDebug("ERROR: mail server explicitly reporting an error.\n", Horde_Imap_Client::DEBUG_INFO);
            break;

        case 'LIMIT':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server has denied the request."),
                Horde_Imap_Client_Exception::LIMIT,
                $ob->status,
                strval($ob->token)
            );

        case 'OVERQUOTA':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The operation failed because the quota has been exceeded on the mail server."),
                Horde_Imap_Client_Exception::OVERQUOTA,
                $ob->status,
                strval($ob->token)
            );

        case 'ALREADYEXISTS':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The object could not be created because it already exists."),
                Horde_Imap_Client_Exception::ALREADYEXISTS,
                $ob->status,
                strval($ob->token)
            );

        case 'NONEXISTENT':
            // Defined by RFC 5530 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The object could not be deleted because it does not exist."),
                Horde_Imap_Client_Exception::NONEXISTENT,
                $ob->status,
                strval($ob->token)
            );

        case 'USEATTR':
            // Defined by RFC 6154 [3]
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The special-use attribute requested for the mailbox is not supported."),
                Horde_Imap_Client_Exception::USEATTR,
                $ob->status,
                strval($ob->token)
            );

        case 'XPROXYREUSE':
            // The proxy connection was reused, so no need to do login tasks.
            $this->_temp['proxyreuse'] = true;
            break;

        default:
            // Unknown response codes SHOULD be ignored - RFC 3501 [7.1]
            break;
        }
    }

}
