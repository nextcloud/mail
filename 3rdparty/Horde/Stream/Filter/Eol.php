<?php
/**
 * Stream filter class to convert EOL characters.
 *
 * Usage:
 *   stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
 *   stream_filter_[app|pre]pend($stream, 'horde_eol',
 *                               [ STREAM_FILTER_[READ|WRITE|ALL] ],
 *                               [ $params ]);
 *
 * $params is an arrat that can contain the following:
 *   - eol: (string) The EOL string to use.
 *          DEFAULT: <CR><LF> ("\r\n")
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package Stream_Filter
 */
class Horde_Stream_Filter_Eol extends php_user_filter
{
    /**
     * Search array.
     *
     * @param mixed
     */
    protected $_search;

    /**
     * Replacement data
     *
     * @param mixed
     */
    protected $_replace;

    /**
     * @see stream_filter_register()
     */
    public function onCreate()
    {
        $eol = isset($this->params['eol']) ? $this->params['eol'] : "\r\n";

        if (!strlen($eol)) {
            $this->_search = array("\r", "\n");
            $this->_replace = '';
        } elseif (in_array($eol, array("\r", "\n"))) {
            $this->_search = array("\r\n", ($eol == "\r") ? "\n" : "\r");
            $this->_replace = $eol;
        } else {
            $this->_search = array("\r\n", "\r", "\n");
            $this->_replace = array("\n", "\n", $eol);
        }

        return true;
    }

    /**
     * @see stream_filter_register()
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = str_replace($this->_search, $this->_replace, $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

}
