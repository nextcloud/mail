<?php
/**
 * An object that provides a way to identify a list of IMAP indices.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 *
 * @property boolean $all  Does this represent an ALL message set?
 * @property array $ids  The list of IDs.
 * @property boolean $largest  Does this represent the largest ID in use?
 * @property string $range_string  Generates a range string consisting of all
 *                                 messages between begin and end of ID list.
 * @property boolean $search_res  Does this represent a search result?
 * @property boolean $sequence  Are these sequence IDs? If false, these are
 *                              UIDs.
 * @property boolean $special  True if this is a "special" ID representation.
 * @property string $tostring  Return the non-sorted string representation.
 * @property string $tostring_sort  Return the sorted string representation.
 */
class Horde_Imap_Client_Ids implements Countable, Iterator, Serializable
{
    /* "Special" representation constants. */
    const ALL = "\01";
    const SEARCH_RES = "\02";
    const LARGEST = "\03";

    /**
     * List of IDs.
     *
     * @var mixed
     */
    protected $_ids = array();

    /**
     * Are IDs message sequence numbers?
     *
     * @var boolean
     */
    protected $_sequence = false;

    /**
     * Are IDs sorted?
     *
     * @var boolean
     */
    protected $_sorted = false;

    /**
     * Constructor.
     *
     * @param mixed $ids         See self::add().
     * @param boolean $sequence  Are $ids message sequence numbers?
     */
    public function __construct($ids = null, $sequence = false)
    {
        $this->add($ids);
        $this->_sequence = $sequence;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'all':
            return ($this->_ids === self::ALL);

        case 'ids':
            return is_array($this->_ids)
                ? $this->_ids
                : array();

        case 'largest':
            return ($this->_ids === self::LARGEST);

        case 'range_string':
            return is_array($this->_ids)
                ? min($this->_ids) . ':' . max($this->_ids)
                : '';

        case 'search_res':
            return ($this->_ids === self::SEARCH_RES);

        case 'sequence':
            return (bool)$this->_sequence;

        case 'special':
            return is_string($this->_ids);

        case 'tostring':
        case 'tostring_sort':
            if ($this->all) {
                return '1:*';
            } elseif ($this->largest) {
                return '*';
            } elseif ($this->search_res) {
                return '$';
            }
            return strval($this->_toSequenceString($name == 'tostring_sort'));
        }
    }

    /**
     */
    public function __toString()
    {
        return $this->tostring;
    }

    /**
     * Add IDs to the current object.
     *
     * @param mixed $ids  Either self::ALL, self::SEARCH_RES, self::LARGEST,
     *                    Horde_Imap_Client_Ids object, array, or sequence
     *                    string.
     */
    public function add($ids)
    {
        if (!is_null($ids)) {
            $add = array();

            if (is_string($ids) &&
                in_array($ids, array(self::ALL, self::SEARCH_RES, self::LARGEST))) {
                $this->_ids = $ids;
                $this->_sorted = false;
                return;
            }

            if ($ids instanceof Horde_Imap_Client_Ids) {
                $add = $ids->ids;
            } elseif (is_array($ids)) {
                $add = $ids;
            } elseif (is_string($ids) || is_integer($ids)) {
                if (is_numeric($ids)) {
                    $add = array($ids);
                } else {
                    $add = $this->_fromSequenceString($ids);
                }
            }

            $this->_ids = is_array($this->_ids)
                ? array_keys(array_flip(array_merge($this->_ids, $add)))
                : $add;
            $this->_sorted = false;
        }
    }

    /**
     * Is this object empty (i.e. does not contain IDs)?
     *
     * @return boolean  True if object is empty.
     */
    public function isEmpty()
    {
        return (is_array($this->_ids) && !count($this->_ids));
    }

    /**
     * Reverses the order of the IDs.
     */
    public function reverse()
    {
        if (is_array($this->_ids)) {
            $this->_ids = array_reverse($this->_ids);
        }
    }

    /**
     * Sorts the IDs numerically.
     */
    public function sort()
    {
        if (!$this->_sorted && is_array($this->_ids)) {
            sort($this->_ids, SORT_NUMERIC);
            $this->_sorted = true;
        }
    }

    /**
     * Create an IMAP message sequence string from a list of indices.
     *
     * Index Format: range_start:range_end,uid,uid2,...
     *
     * @param boolean $nosort  Numerically sort the IDs before creating the
     *                         range?
     *
     * @return string  The IMAP message sequence string.
     */
    protected function _toSequenceString($sort = true)
    {
        if (empty($this->_ids)) {
            return '';
        }

        $in = $this->_ids;

        if ($sort) {
            sort($in, SORT_NUMERIC);
        }

        $first = $last = array_shift($in);
        $i = count($in) - 1;
        $out = array();

        reset($in);
        while (list($key, $val) = each($in)) {
            if (($last + 1) == $val) {
                $last = $val;
            }

            if (($i == $key) || ($last != $val)) {
                if ($last == $first) {
                    $out[] = $first;
                    if ($i == $key) {
                        $out[] = $val;
                    }
                } else {
                    $out[] = $first . ':' . $last;
                    if (($i == $key) && ($last != $val)) {
                        $out[] = $val;
                    }
                }
                $first = $last = $val;
            }
        }

        return empty($out)
            ? $first
            : implode(',', $out);
    }

    /**
     * Parse an IMAP message sequence string into a list of indices.
     *
     * @see _toSequenceString()
     *
     * @param string $str  The IMAP message sequence string.
     *
     * @return array  An array of indices.
     */
    protected function _fromSequenceString($str)
    {
        $ids = array();
        $str = trim($str);

        if (!strlen($str)) {
            return $ids;
        }

        $idarray = explode(',', $str);

        reset($idarray);
        while (list(,$val) = each($idarray)) {
            $range = explode(':', $val);
            if (isset($range[1])) {
                for ($i = min($range), $j = max($range); $i <= $j; ++$i) {
                    $ids[] = $i;
                }
            } else {
                $ids[] = $val;
            }
        }

        return $ids;
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return is_array($this->_ids)
            ? count($this->_ids)
           : 0;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return is_array($this->_ids)
            ? current($this->_ids)
            : null;
    }

    /**
     */
    public function key()
    {
        return is_array($this->_ids)
            ? key($this->_ids)
            : null;
    }

    /**
     */
    public function next()
    {
        if (is_array($this->_ids)) {
            next($this->_ids);
        }
    }

    /**
     */
    public function rewind()
    {
        if (is_array($this->_ids)) {
            reset($this->_ids);
        }
    }

    /**
     */
    public function valid()
    {
        return !is_null($this->key());
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        $save = array();

        if ($this->_sequence) {
            $save['s'] = 1;
        }

        if ($this->_sorted) {
            $save['is'] = 1;
        }

        switch ($this->_ids) {
        case self::ALL:
            $save['a'] = true;
            break;

        case self::LARGEST:
            $save['l'] = true;
            break;

        case self::SEARCH_RES:
            $save['sr'] = true;
            break;

        default:
            $save['i'] = strval($this);
            break;
        }

        return serialize($save);
    }

    /**
     */
    public function unserialize($data)
    {
        $save = @unserialize($data);

        $this->_sequence = !empty($save['s']);
        $this->_sorted = !empty($save['is']);

        if (isset($save['a'])) {
            $this->_ids = self::ALL;
        } elseif (isset($save['l'])) {
            $this->_ids = self::LARGEST;
        } elseif (isset($save['sr'])) {
            $this->_ids = self::SEARCH_RES;
        } elseif (isset($save['i'])) {
            $this->add($save['i']);
        }
    }

}
