<?php
/**
 * Horde optimized interface to the MaxMind IP Address->Country listing.
 *
 * Based on PHP geoip.inc library by MaxMind LLC:
 *   http://www.maxmind.com/download/geoip/api/php/
 *
 * Originally based on php version of the geoip library written in May
 * 2002 by jim winstead <jimw@apache.org>
 *
 * Copyright 2003 MaxMind LLC
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Nls
 */
class Horde_Nls_Geoip
{
    /* TODO */
    const GEOIP_COUNTRY_BEGIN = 16776960;
    const STRUCTURE_INFO_MAX_SIZE = 20;
    const STANDARD_RECORD_LENGTH = 3;

    /**
     * Country list.
     *
     * @var array
     */
    protected $_countryCodes = array(
        '', 'AP', 'EU', 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO',
        'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AZ', 'BA', 'BB', 'BD', 'BE',
        'BF', 'BG', 'BH', 'BI', 'BJ', 'BM', 'BN', 'BO', 'BR', 'BS', 'BT',
        'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI',
        'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CX', 'CY', 'CZ',
        'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER',
        'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'FX', 'GA', 'UK',
        'GD', 'GE', 'GF', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR',
        'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
        'ID', 'IE', 'IL', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JM', 'JO',
        'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY',
        'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV',
        'LY', 'MA', 'MC', 'MD', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO',
        'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU',
        'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN',
        'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RU', 'RW', 'SA',
        'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM',
        'SN', 'SO', 'SR', 'ST', 'SV', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG',
        'TH', 'TJ', 'TK', 'TM', 'TN', 'TO', 'TP', 'TR', 'TT', 'TV', 'TW',
        'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG',
        'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'YU', 'ZA', 'ZM', 'ZR',
        'ZW', 'A1', 'A2', 'O1'
    );

    /**
     * The location of the GeoIP database.
     *
     * @var string
     */
    protected $_datafile;

    /**
     * The open filehandle to the GeoIP database.
     *
     * @var resource
     */
    protected $_fh;

    /**
     * Constructor.
     *
     * @param string $datafile         The location of the GeoIP database.
     */
    public function __construct($datafile)
    {
        $this->_datafile = $datafile;
    }

    /**
     * Open the GeoIP database.
     *
     * @return boolean  False on error.
     */
    protected function _open()
    {
        /* Return if we already have an object. */
        if (!empty($this->_fh)) {
            return true;
        }

        /* Return if no datafile specified. */
        if (empty($this->_datafile)) {
            return false;
        }

        $this->_fh = fopen($this->_datafile, 'rb');
        if (!$this->_fh) {
            return false;
        }

        $filepos = ftell($this->_fh);
        fseek($this->_fh, -3, SEEK_END);
        for ($i = 0; $i < self::STRUCTURE_INFO_MAX_SIZE; ++$i) {
            $delim = fread($this->_fh, 3);
            if ($delim == (chr(255) . chr(255) . chr(255))) {
                break;
            } else {
                fseek($this->_fh, -4, SEEK_CUR);
            }
        }
        fseek($this->_fh, $filepos, SEEK_SET);

        return true;
    }

    /**
     * Returns the country ID and Name for a given hostname.
     *
     * @param string $name  The hostname.
     *
     * @return mixed  An array with 'code' as the country code and 'name' as
     *                the country name, or false if not found.
     */
    public function getCountryInfo($name)
    {
        if (Horde_Util::extensionExists('geoip')) {
            $id = @geoip_country_code_by_name($name);
            $cname = @geoip_country_name_by_name($name);
            return (!empty($id) && !empty($cname)) ?
                array('code' => Horde_String::lower($id), 'name' => $cname):
                false;
        }

        $id = $this->countryIdByName($name);
        if (!empty($id)) {
            $code = $this->_countryCodes[$id];
            return array(
                'code' => Horde_String::lower($code),
                'name' => $this->_getName($code)
            );
        }

        return false;
    }

    /**
     * Returns the country ID for a hostname.
     *
     * @param string $name  The hostname.
     *
     * @return integer  The GeoIP country ID.
     */
    public function countryIdByName($name)
    {
        if (!$this->_open()) {
            return false;
        }

        $addr = gethostbyname($name);
        if (!$addr || ($addr == $name)) {
            return false;
        }

        return $this->countryIdByAddr($addr);
    }

    /**
     * Returns the country abbreviation (2-letter) for a hostname.
     *
     * @param string $name  The hostname.
     *
     * @return integer  The country abbreviation.
     */
    public function countryCodeByName($name)
    {
        if ($this->_open()) {
            $country_id = $this->countryIdByName($name);
            if ($country_id !== false) {
                return $this->_countryCodes[$country_id];
            }
        }

        return false;
    }

    /**
     * Returns the country name for a hostname.
     *
     * @param string $name  The hostname.
     *
     * @return integer  The country name.
     */
    public function countryNameByName($name)
    {
        if ($this->_open()) {
            $country_id = $this->countryCodeByName($name);
            if ($country_id !== false) {
                return $this->_getName($country_id);
            }
        }

        return false;
    }

    /**
     * Returns the country ID for an IP Address.
     *
     * @param string $addr  The IP Address.
     *
     * @return integer  The GeoIP country ID.
     */
    public function countryIdByAddr($addr)
    {
        if (!$this->_open()) {
            return false;
        }

        $ipnum = ip2long($addr);
        $country = $this->_seekCountry($ipnum);

        return ($country === false)
            ? ''
            : ($this->_seekCountry($ipnum) - self::GEOIP_COUNTRY_BEGIN);
    }

    /**
     * Returns the country abbreviation (2-letter) for an IP Address.
     *
     * @param string $addr  The IP Address.
     *
     * @return integer  The country abbreviation.
     */
    public function countryCodeByAddr($addr)
    {
        if ($this->_open()) {
            $country_id = $this->countryIdByAddr($addr);
            if ($country_id !== false) {
                return $this->_countryCodes[$country_id];
            }
        }

        return false;
    }

    /**
     * Returns the country name for an IP address.
     *
     * @param string $addr  The IP address.
     *
     * @return mixed  The country name.
     */
    public function countryNameByAddr($addr)
    {
        if ($this->_open()) {
            $country_id = $this->countryCodeByAddr($addr);
            if ($country_id !== false) {
                return $this->_getName($country_id);
            }
        }

        return false;
    }

    /**
     * Finds a country by IP Address in the GeoIP database.
     *
     * @param string $ipnum  The IP Address to search for.
     *
     * @return mixed  The country ID or false if not found.
     */
    protected function _seekCountry($ipnum)
    {
        $offset = 0;

        for ($depth = 31; $depth >= 0; --$depth) {
            if (fseek($this->_fh, 2 * self::STANDARD_RECORD_LENGTH * $offset, SEEK_SET) != 0) {
                return false;
            }
            $buf = fread($this->_fh, 2 * self::STANDARD_RECORD_LENGTH);
            $x = array(0, 0);

            for ($i = 0; $i < 2; ++$i) {
                for ($j = 0; $j < self::STANDARD_RECORD_LENGTH; ++$j) {
                    $x[$i] += ord($buf[self::STANDARD_RECORD_LENGTH * $i + $j]) << ($j * 8);
                }
            }
            if ($ipnum & (1 << $depth)) {
                if ($x[1] >= self::GEOIP_COUNTRY_BEGIN) {
                    return $x[1];
                }
                $offset = $x[1];
            } else {
                if ($x[0] >= self::GEOIP_COUNTRY_BEGIN) {
                    return $x[0];
                }
                $offset = $x[0];
            }
        }

        return false;
    }

    /**
     * Given a 2-letter country code, returns a country string.
     *
     * @param string $code  The country code.
     *
     * @return string  The country string.
     */
    protected function _getName($code)
    {
        $code = Horde_String::upper($code);

        $geoip_codes = array(
            'AP' => Horde_Nls_Translation::t("Asia/Pacific Region"),
            'EU' => Horde_Nls_Translation::t("Europe"),
            'A1' => Horde_Nls_Translation::t("Anonymous Proxy"),
            'A2' => Horde_Nls_Translation::t("Satellite Provider"),
            'O1' => Horde_Nls_Translation::t("Other")
        );

        return isset($geoip_codes[$code])
            ? $geoip_codes[$code]
            : strval(Horde_Nls::getCountryISO($code));
    }

}
