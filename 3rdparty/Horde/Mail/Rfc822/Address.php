<?php
/**
 * Object representation of a RFC 822 e-mail address.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */

/**
 * Object representation of a RFC 822 e-mail address.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 *
 * @property string $bare_address  The bare mailbox@host address.
 * @property string $encoded  The full MIME/IDN encoded address (UTF-8).
 * @property string $host  Returns the host part (UTF-8).
 * @property string $host_idn  Returns the IDN encoded host part.
 * @property string $label  The shorthand label for this address.
 * @property string $personal  The personal part (UTF-8).
 * @property string $personal_encoded  The MIME encoded personal part (UTF-8).
 * @property boolean $valid  Returns true if there is enough information in
 *                           object to create a valid address.
 */
class Horde_Mail_Rfc822_Address extends Horde_Mail_Rfc822_Object
{
    /**
     * Comments associated with the personal phrase.
     *
     * @var array
     */
    public $comment = array();

    /**
     * Local-part of the address.
     *
     * @var string
     */
    public $mailbox = null;

    /**
     * Hostname of the address.
     *
     * @var string
     */
    protected $_host = null;

    /**
     * Personal part of the address.
     *
     * @var string
     */
    protected $_personal = null;

    /**
     * Constructor.
     *
     * @param string $addresses  If set, address is parsed and used as the
     *                           object address. Address is not validated;
     *                           first e-mail address parsed is used.
     */
    public function __construct($address = null)
    {
        if (!is_null($address)) {
            $rfc822 = new Horde_Mail_Rfc822();
            $addr = $rfc822->parseAddressList($address);
            if (count($addr)) {
                foreach ($addr[0] as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'host':
            $value = ltrim($value, '@');
            $this->_host = function_exists('idn_to_utf8')
                ? strtolower(idn_to_utf8($value))
                : strtolower($value);
            break;

        case 'personal':
            $this->_personal = strlen($value)
                ? Horde_Mime::decode($value)
                : null;
            break;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'bare_address':
            return is_null($this->host)
                ? $this->mailbox
                : $this->mailbox . '@' . $this->host;

        case 'encoded':
            return $this->writeAddress(true);

        case 'host':
            return $this->_host;

        case 'host_idn':
            return function_exists('idn_to_ascii')
                ? idn_to_ascii($this->_host)
                : $this->host;

        case 'label':
            return is_null($this->_personal)
                ? $this->bare_address
                : $this->_personal;

        case 'personal':
            return $this->_personal;

        case 'personal_encoded':
            return Horde_Mime::encode($this->personal);

        case 'valid':
            return (bool)strlen($this->mailbox);

        default:
            return null;
        }
    }

    /**
     */
    protected function _writeAddress($opts)
    {
        $rfc822 = new Horde_Mail_Rfc822();

        $address = $rfc822->encode($this->mailbox, 'address');
        $host = empty($opts['idn']) ? $this->host : $this->host_idn;
        if (strlen($host)) {
            $address .= '@' . $host;
        }
        $personal = $this->personal;
        if (strlen($personal)) {
            if (!empty($opts['encode'])) {
                $personal = Horde_Mime::encode($this->personal, $opts['encode']);
            }
            $personal = $rfc822->encode($personal, 'personal');
        }

        return (strlen($personal) && ($personal != $address))
            ? $personal . ' <' . $address . '>'
            : $address;
    }

    /**
     */
    public function match($ob)
    {
        if (!($ob instanceof Horde_Mail_Rfc822_Address)) {
            $ob = new Horde_Mail_Rfc822_Address($ob);
        }

        return ($this->bare_address == $ob->bare_address);
    }

    /**
     * Do a case-insensitive match on the address. Per RFC 822/2822/5322,
     * although the host portion of an address is case-insensitive, the
     * mailbox portion is platform dependent.
     *
     * @param mixed $ob  Address data.
     *
     * @return boolean  True if the data reflects the same case-insensitive
     *                  address.
     */
    public function matchInsensitive($ob)
    {
        if (!($ob instanceof Horde_Mail_Rfc822_Address)) {
            $ob = new Horde_Mail_Rfc822_Address($ob);
        }

        return (Horde_String::lower($this->bare_address) == Horde_String::lower($ob->bare_address));
    }

    /**
     * Do a case-insensitive match on the address for a given domain.
     * Matches as many parts of the subdomain in the address as is given in
     * the input.
     *
     * @param string $domain  Domain to match.
     *
     * @return boolean  True if the address matches the given domain.
     */
    public function matchDomain($domain)
    {
        $host = $this->host;
        if (is_null($host)) {
            return false;
        }

        $match_domain = explode('.', $domain);
        $match_host = array_slice(explode('.', $host), count($match_domain) * -1);

        return (strcasecmp($domain, implode('.', $match_host)) === 0);
    }

}
