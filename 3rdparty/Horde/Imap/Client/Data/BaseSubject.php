<?php
/**
 * Determines the "base subject" of a string (RFC 5256 [2.1]).
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * getBaseSubject() code adapted from imap-base-subject.c (Dovecot 1.2)
 *   Original code released under the LGPL-2.0.1
 *   Copyright (c) 2002-2008 Timo Sirainen <tss@iki.fi>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_BaseSubject
{
    /**
     * The base subject.
     *
     * @var string
     */
    protected $_subject;

    /**
     * Constructor.
     *
     * @param string $str     The subject string.
     * @param array $options  Additional options:
     *   - keepblob: (boolean) Don't remove any "blob" information (i.e. text
     *               leading text between square brackets) from string.
     *
     * @return string  The cleaned up subject string.
     */
    public function __construct($str, array $opts = array())
    {
        // Rule 1a: MIME decode.
        $str = Horde_Mime::decode($str);

        // Rule 1b: Remove superfluous whitespace.
        $str = preg_replace("/[\t\r\n ]+/", ' ', $str);

        if (!strlen($str)) {
            $this->_subject = '';
        }

        do {
            /* (2) Remove all trailing text of the subject that matches the
             * the subj-trailer ABNF, repeat until no more matches are
             * possible. */
            $str = preg_replace("/(?:\s*\(fwd\)\s*)+$/i", '', $str);

            do {
                /* (3) Remove all prefix text of the subject that matches the
                 * subj-leader ABNF. */
                $found = $this->_removeSubjLeader($str, !empty($opts['keepblob']));

                /* (4) If there is prefix text of the subject that matches
                 * the subj-blob ABNF, and removing that prefix leaves a
                 * non-empty subj-base, then remove the prefix text. */
                $found = (empty($opts['keepblob']) && $this->_removeBlobWhenNonempty($str)) || $found;

                /* (5) Repeat (3) and (4) until no matches remain. */
            } while ($found);

            /* (6) If the resulting text begins with the subj-fwd-hdr ABNF and
             * ends with the subj-fwd-trl ABNF, remove the subj-fwd-hdr and
             * subj-fwd-trl and repeat from step (2). */
        } while ($this->_removeSubjFwdHdr($str));

        $this->_subject = $str;
    }

    /**
     * Return the "base subject" defined in RFC 5256 [2.1].
     *
     * @return string  The base subject.
     */
    public function __toString()
    {
        return $this->_subject;
    }

    /**
     * Remove all prefix text of the subject that matches the subj-leader
     * ABNF.
     *
     * @param string &$str       The subject string.
     * @param boolean $keepblob  Remove blob information?
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeSubjLeader(&$str, $keepblob = false)
    {
        $ret = false;

        if (!strlen($str)) {
            return $ret;
        }

        if ($len = strspn($str, " \t")) {
            $str = substr($str, $len);
            $ret = true;
        }

        $i = 0;

        if (!$keepblob) {
            while (isset($str[$i]) && ($str[$i] == '[')) {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        if (stripos($str, 're', $i) === 0) {
            $i += 2;
        } elseif (stripos($str, 'fwd', $i) === 0) {
            $i += 3;
        } elseif (stripos($str, 'fw', $i) === 0) {
            $i += 2;
        } else {
            return $ret;
        }

        $i += strspn($str, " \t", $i);

        if (!$keepblob) {
            while (isset($str[$i]) && ($str[$i] == '[')) {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        if (!isset($str[$i]) || ($str[$i] != ':')) {
            return $ret;
        }

        $str = substr($str, ++$i);

        return true;
    }

    /**
     * Remove "[...]" text.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeBlob($str, $i)
    {
        if ($str[$i] != '[') {
            return false;
        }

        ++$i;

        for ($cnt = strlen($str); $i < $cnt; ++$i) {
            if ($str[$i] == ']') {
                break;
            }

            if ($str[$i] == '[') {
                return false;
            }
        }

        if ($i == ($cnt - 1)) {
            return false;
        }

        ++$i;

        if ($str[$i] == ' ') {
            ++$i;
        }

        return $i;
    }

    /**
     * Remove "[...]" text if it doesn't result in the subject becoming
     * empty.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeBlobWhenNonempty(&$str)
    {
        if ($str &&
            ($str[0] == '[') &&
            (($i = $this->_removeBlob($str, 0)) !== false) &&
            ($i != strlen($str))) {
            $str = substr($str, $i);
            return true;
        }

        return false;
    }

    /**
     * Remove a "[fwd: ... ]" string.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeSubjFwdHdr(&$str)
    {
        if ((stripos($str, '[fwd:') !== 0) || (substr($str, -1) != ']')) {
            return false;
        }

        $str = substr($str, 5, -1);
        return true;
    }

}
