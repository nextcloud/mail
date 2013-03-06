<?php
/**
 * Object representation of an IMAP number (RFC 3501 [4.2]).
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
class Horde_Imap_Client_Data_Format_Number extends Horde_Imap_Client_Data_Format
{
    /**
     */
    public function __toString()
    {
        return strval(intval($this->_data));
    }

    /**
     */
    public function verify()
    {
        if (!is_numeric($this->_data)) {
            throw new Horde_Imap_Client_Data_Format_Exception('Illegal character in IMAP number.');
        }
    }

}
