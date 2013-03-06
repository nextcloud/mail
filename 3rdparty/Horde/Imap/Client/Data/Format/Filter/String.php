<?php
/**
 * Stream filter to analyze an IMAP string to determine how to send to the
 * server.
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
class Horde_Imap_Client_Data_Format_Filter_String extends php_user_filter
{
    /**
     * @see stream_filter_register()
     */
    public function onCreate()
    {
        $this->params->binary = false;
        $this->params->literal = false;
        // no_quote_list is used below as a config option
        $this->params->quoted = false;

        return true;
    }

    /**
     * @see stream_filter_register()
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        $skip = false;

        while ($bucket = stream_bucket_make_writeable($in)) {
            if (!$skip) {
                for ($i = 0; $i < $bucket->datalen; ++$i) {
                    $chr = ord($bucket->data[$i]);

                    switch ($chr) {
                    case 0: // null
                        $this->params->binary = true;
                        $this->params->literal = true;

                        // No need to scan input anymore.
                        $skip = true;
                        break 2;

                    case 10: // LF
                    case 13: // CR
                        $this->params->literal = true;
                        break;

                    case 32: // SPACE
                    case 34: // "
                    case 40: // (
                    case 41: // )
                    case 92: // \
                    case 123: // {
                    case 127: // DEL
                        // These are all invalid ATOM characters.
                        $this->params->quoted = true;
                        break;

                    case 37: // %
                    case 42: // *
                        // These are not quoted if being used as wildcards.
                        if (empty($this->params->no_quote_list)) {
                            $this->params->quoted = true;
                        }
                        break;

                    default:
                        if ($chr < 32) {
                            // CTL characters must be, at a minimum, quoted.
                            $this->params->quoted = true;
                        } elseif ($chr > 127) {
                            // 8-bit chars must be in a literal.
                            $this->params->literal = true;
                        }
                        break;
                    }
                }
            }

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        if ($this->params->literal) {
            $this->params->quoted = false;
        }

        return PSFS_PASS_ON;
    }

}
