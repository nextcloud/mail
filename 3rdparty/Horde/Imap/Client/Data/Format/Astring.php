<?php
/**
 * Object representation of an IMAP astring (atom or string) (RFC 3501 [4.3]).
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
class Horde_Imap_Client_Data_Format_Astring extends Horde_Imap_Client_Data_Format_String
{
    /**
     */
    public function quoted()
    {
        return $this->_filter->quoted || !$this->_data->length();
    }

}
