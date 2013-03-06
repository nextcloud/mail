<?php
/**
 * An object to provide tokenization of an IMAP data stream.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Tokenize implements Iterator
{
    /**
     * Current data.
     *
     * @var mixed
     */
    protected $_current = false;

    /**
     * Current key.
     *
     * @var integer
     */
    protected $_key = false;

    /**
     * Parent Tokenize object.
     *
     * @var Horde_Imap_Client_Tokenize
     */
    protected $_parent;

    /**
     * Stream starting position.
     *
     * @var integer
     */
    protected $_start = 0;

    /**
     * Is this object the currently active stream reader?
     *
     * @var boolean
     */
    public $active = true;

    /**
     * Data stream.
     *
     * @var Horde_Stream
     */
    public $stream;

    /**
     * Constructor.
     *
     * @param mixed $data    Data to add (string, resource, or Horde_Stream
     *                       object), or a Tokenize object.
     */
    public function __construct($data = null)
    {
        if ($data instanceof Horde_Imap_Client_Tokenize) {
            $this->stream = $data->stream;
            $this->_parent = $data;
            $this->_start = ftell($this->stream->stream);
        } else {
            $this->stream = new Horde_Stream_Temp();

            if (!is_null($data)) {
                $this->add($data);
            }
        }
    }

    /**
     */
    public function __toString()
    {
        $pos = ftell($this->stream->stream);
        $out = $this->_current . ' ' . $this->stream->getString();
        fseek($this->stream->stream, $pos);
        return $out;
    }

    /**
     * Add data to buffer.
     *
     * @param mixed $data  Data to add (string, resource, or Horde_Stream
     *                     object).
     */
    public function add($data)
    {
        if (!$this->_parent) {
            $this->stream->add($data);
        }
    }

    /**
     * Flush the remaining entries left in the iterator.
     *
     * @param boolean $return_entry  If true, don't return entries.
     *
     * @return array  The remaining iterator entries if $return_entry is true.
     */
    public function flushIterator($return_entry = true)
    {
        $out = array();

        while ($this->valid()) {
            if ($return_entry) {
                $out[] = $this->current();
            }
            $this->next();
        }

        return $out;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     */
    public function next()
    {
        $this->_current = $this->_parseStream();
        $this->_key = ($this->_current === false)
            ? false
            : (($this->_key === false) ? 0 : ($this->_key + 1));

        return $this->_current;
    }

    /**
     */
    public function rewind()
    {
        fseek($this->stream->stream, $this->_start);
        $this->_current = $this->_key = false;

        if ($this->_parent) {
            $this->_parent->active = false;
        }

        return $this->next();
    }

    /**
     */
    public function valid()
    {
        return ($this->_key !== false);
    }

    /**
     * Returns the next token and increments the internal stream pointer.
     *
     * @return mixed  Either a string, array, false, or null.
     */
    protected function _parseStream()
    {
        $in_quote = false;
        $text = '';

        /* If active is false, we delegated the stream to a child tokenizer,
         * which did not reach the end of the list.  Thus, we have to manually
         * iterate through until we reach a closing paren. */
        if (!$this->active) {
            $this->active = true;

            $old_parent = $this->_parent;
            $this->_parent = null;
            while ($this->next() !== false) {}
            $this->_parent = $old_parent;
        }

        while (($c = fgetc($this->stream->stream)) !== false) {
            switch ($c) {
            case '\\':
                $text .= $in_quote
                    ? fgetc($this->stream->stream)
                    : $c;
                break;

            case '"':
                if ($in_quote) {
                    return $text;
                } else {
                    $in_quote = true;
                }
                break;

            default:
                if ($in_quote) {
                    $text .= $c;
                    break;
                }

                switch ($c) {
                case '(':
                    $this->active = false;
                    return new Horde_Imap_Client_Tokenize($this);

                case ')':
                    if (strlen($text)) {
                        fseek($this->stream->stream, -1, SEEK_CUR);
                        break 3;
                    }

                    if ($this->_parent) {
                        $this->_parent->active = true;
                        $this->_current = $this->_key = false;
                    }
                    return false;

                case '~':
                    // Ignore binary string identifier. PHP strings are
                    // binary-safe.
                    break;

                case '{':
                    $literal_len = $this->stream->getToChar('}');
                    return stream_get_contents($this->stream->stream, $literal_len);

                case ' ':
                    if (strlen($text)) {
                        break 3;
                    }
                    break;

                default:
                    $text .= $c;
                    break;
                }
                break;
            }
        }

        return strlen($text)
            ? ((strcasecmp($text, 'NIL') === 0) ? null : $text)
            : false;
    }

}
