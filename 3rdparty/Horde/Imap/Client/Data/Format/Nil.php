<?php
/**
 * Object representation of an IMAP NIL (RFC 3501 [4.5]).
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
class Horde_Imap_Client_Data_Format_Nil extends Horde_Imap_Client_Data_Format
{
    /**
     */
    public function __construct($data = null)
    {
        // Don't store any data in object.
    }

    /**
     */
    public function __toString()
    {
        return '';
    }

    /**
     */
    public function escape()
    {
        return 'NIL';
    }

}
