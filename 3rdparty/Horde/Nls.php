<?php
/**
 * The Horde_Nls:: class provides Native Language Support. This includes
 * common methods for handling language data, timezones, and hostname->country
 * lookups.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Nls
 */
class Horde_Nls
{
    /**
     * DNS resolver.
     *
     * @var Net_DNS2_Resolver
     */
    static public $dnsResolver;

    /**
     * Cached values.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Check to see if character set is valid for htmlspecialchars() calls.
     *
     * @param string $charset  The character set to check.
     *
     * @return boolean  Is charset valid for the current system?
     */
    static public function checkCharset($charset)
    {
        if (is_null($charset) || empty($charset)) {
            return false;
        }

        $valid = true;

        ini_set('track_errors', 1);
        @htmlspecialchars('', ENT_COMPAT, $charset);
        if (isset($php_errormsg)) {
            $valid = false;
        }
        ini_restore('track_errors');

        return $valid;
    }

    /**
     * Returns a list of available timezones.
     *
     * @return array  List of timezones.
     */
    static public function getTimezones()
    {
        $timezones = DateTimeZone::listIdentifiers();
        return array_combine($timezones, $timezones);
    }

    /**
     * Get the locale info returned by localeconv(), but cache it, to
     * avoid repeated calls.
     *
     * @return array  The results of localeconv().
     */
    static public function getLocaleInfo()
    {
        if (!isset(self::$_cache['lc_info'])) {
            self::$_cache['lc_info'] = localeconv();
        }

        return self::$_cache['lc_info'];
    }

    /**
     * Get the language info returned by nl_langinfo(), but cache it, to
     * avoid repeated calls.
     *
     * @param const $item  The langinfo item to return.
     *
     * @return array  The results of nl_langinfo().
     */
    static public function getLangInfo($item)
    {
        if (!function_exists('nl_langinfo')) {
            return false;
        }

        if (!isset(self::$_cache['nl_info'])) {
            self::$_cache['nl_info'] = array();
        }

        if (!isset(self::$_cache['nl_info'][$item])) {
            self::$_cache['nl_info'][$item] = nl_langinfo($item);
        }

        return self::$_cache['nl_info'][$item];
    }

    /**
     * Get country information from a hostname or IP address.
     *
     * @param string $host      The hostname or IP address.
     * @param string $datafile  The datafile for the GeoIP lookup. If not set,
     *                          will skip this lookup.
     *
     * @return mixed  On success, return an array with the following entries:
     *                'code'  =>  Country Code
     *                'name'  =>  Country Name
     *                On failure, return false.
     */
    static public function getCountryByHost($host, $datafile = null)
    {
        /* List of generic domains that we know is not in the country TLD
           list. See: http://www.iana.org/gtld/gtld.htm */
        $generic = array(
            'aero', 'biz', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'mil',
            'museum', 'name', 'net', 'org', 'pro'
        );

        $checkHost = null;
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            if (isset(self::$dnsResolver)) {
                try {
                    $response = self::$dnsResolver->query($host, 'PTR');
                    foreach ($response->answer as $val) {
                        if (isset($val->ptrdname)) {
                            $checkHost = $val->ptrdname;
                            break;
                        }
                    }
                } catch (Net_DNS2_Exception $e) {}
            }
            if (is_null($checkHost)) {
                $checkHost = @gethostbyaddr($host);
            }
        } else {
            $checkHost = $host;
        }

        /* Get the TLD of the hostname. */
        $pos = strrpos($checkHost, '.');
        if ($pos === false) {
            return false;
        }
        $domain = Horde_String::lower(substr($checkHost, $pos + 1));

        /* Try lookup via TLD first. */
        if (!in_array($domain, $generic)) {
            $name = self::tldLookup($domain);
            if ($name) {
                return array(
                    'code' => $domain,
                    'name' => $name
                );
            }
        }

        /* Try GeoIP lookup next. */
        $geoip = new Horde_Nls_Geoip($datafile);
        return $geoip->getCountryInfo($checkHost);
    }

    /**
     * Do a top level domain (TLD) lookup.
     *
     * @param string $code  A 2-letter country code.
     *
     * @return mixed  The localized country name, or null if not found.
     */
    static public function tldLookup($code)
    {
        if (!isset(self::$_cache['tld'])) {
            include __DIR__ . '/Nls/Tld.php';
            self::$_cache['tld'] = $tld;
        }

        $code = Horde_String::lower($code);

        return isset(self::$_cache['tld'][$code])
            ? self::$_cache['tld'][$code]
            : null;
    }

    /**
     * Returns either a specific or all ISO-3166 country names.
     *
     * @param string $code  The ISO 3166 country code.
     *
     * @return mixed  If a country code has been requested will return the
     *                corresponding country name. If empty will return an
     *                array of all the country codes and their names.
     */
    static public function getCountryISO($code = null)
    {
        if (!isset(self::$_cache['iso3166'])) {
            include __DIR__ . '/Nls/Countries.php';
            self::$_cache['iso3166'] = $countries;
        }

        if (empty($code)) {
            return self::$_cache['iso3166'];
        }

        $code = Horde_String::upper($code);

        return isset(self::$_cache['iso3166'][$code])
            ? self::$_cache['iso3166'][$code]
            : null;
    }

    /**
     * Returns either a specific or all ISO-639 language names.
     *
     * @param string $code  The ISO 639 language code.
     *
     * @return mixed  If a language code has been requested will return the
     *                corresponding language name. If empty will return an
     *                array of all the language codes (keys) and their names
     *                (values).
     */
    static public function getLanguageISO($code = null)
    {
        if (!isset(self::$_cache['iso639'])) {
            include __DIR__ . '/Nls/Languages.php';
            self::$_cache['iso639'] = $languages;
        }

        if (empty($code)) {
            return self::$_cache['iso639'];
        }

        $code = substr(Horde_String::lower(trim($code)), 0, 2);

        return isset(self::$_cache['iso639'][$code])
            ? self::$_cache['iso639'][$code]
            : null;
    }

}
