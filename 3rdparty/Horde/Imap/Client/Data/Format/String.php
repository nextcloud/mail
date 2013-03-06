<?php
/**
 * Object representation of an IMAP string (RFC 3501 [4.3]).
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
class Horde_Imap_Client_Data_Format_String extends Horde_Imap_Client_Data_Format
{
    /**
     * String filter parameters.
     *
     * @var string
     */
    protected $_filter;

    /**
     */
    public function __construct($data)
    {
        /* String data is stored in a stream. */
        $this->_data = new Horde_Stream_Temp();

        stream_filter_register('horde_imap_client_string', 'Horde_Imap_Client_Data_Format_Filter_String');

        $this->_filter = $this->_filterParams();
        $res = stream_filter_append($this->_data->stream, 'horde_imap_client_string', STREAM_FILTER_WRITE, $this->_filter);

        $this->_data->add($data);

        stream_filter_remove($res);
    }

    /**
     * Return the base string filter parameters.
     *
     * @return object  Filter parameters.
     */
    protected function _filterParams()
    {
        return new stdClass;
    }

    /**
     */
    public function __toString()
    {
        return $this->_data->getString(0);
    }

    /**
     */
    public function escape()
    {
        if ($this->literal()) {
            throw new Horde_Imap_Client_Data_Format_Exception('String requires literal to output.');
        }

        return $this->quoted()
            ? stream_get_contents($this->escapeStream())
            : $this->_data->getString(0);
    }

    /**
     * Return the escaped string as a stream.
     *
     * @return resource  The IMAP escaped stream.
     */
    public function escapeStream()
    {
        if ($this->literal()) {
            throw new Horde_Imap_Client_Data_Format_Exception('String requires literal to output.');
        }

        rewind($this->_data->stream);

        $stream = new Horde_Stream_Temp();
        $stream->add($this->_data, true);

        stream_filter_register('horde_imap_client_string_quote', 'Horde_Imap_Client_Data_Format_Filter_Quote');
        stream_filter_append($stream->stream, 'horde_imap_client_string_quote', STREAM_FILTER_READ);

        return $stream->stream;
    }

    /**
     * Does this data item require quoted string output?
     *
     * @return boolean  True if quoted output is required.
     */
    public function quoted()
    {
        /* IMAP strings MUST be quoted if they are not a literal. */
        return (!isset($this->_filter) || !$this->_filter->literal);
    }

    /**
     * Force item to be output quoted.
     */
    public function forceQuoted()
    {
        $this->_filter = $this->_filterParams();
        $this->_filter->binary = false;
        $this->_filter->literal = false;
        $this->_filter->quoted = true;
    }

    /**
     * Does this data item require literal string output?
     *
     * @return boolean  True if literal output is required.
     */
    public function literal()
    {
        return (isset($this->_filter) && $this->_filter->literal);
    }

    /**
     * Force item to be output as a literal.
     */
    public function forceLiteral()
    {
        $this->_filter = $this->_filterParams();
        // Keep binary status, if set
        $this->_filter->literal = true;
        $this->_filter->quoted = false;
    }

    /**
     * If literal output, is the data binary?
     *
     * @return boolean  True if the literal output is binary.
     */
    public function binary()
    {
        return (isset($this->_filter) && $this->_filter->binary);
    }

    /**
     * Force item to be output as a binary literal.
     */
    public function forceBinary()
    {
        $this->_filter = $this->_filterParams();
        $this->_filter->binary = true;
        $this->_filter->literal = true;
        $this->_filter->quoted = false;
    }

}
