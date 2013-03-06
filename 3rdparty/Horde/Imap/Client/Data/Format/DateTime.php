<?php
/**
 * Object representation of an IMAP date-time string (RFC 3501 [9]).
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
class Horde_Imap_Client_Data_Format_DateTime extends Horde_Imap_Client_Data_Format_Date
{
    /**
     */
    public function __toString()
    {
        return $this->_data->format('j-M-Y H:i:s O');
    }

    /**
     */
    public function escape()
    {
        return '"' . strval($this) . '"';
    }

}
