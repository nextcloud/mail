<?php
/**
 * Object representation of an IMAP nstring (NIL or string) (RFC 3501 [4.5]).
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
class Horde_Imap_Client_Data_Format_Nstring extends Horde_Imap_Client_Data_Format_String
{
    /**
     */
    public function __construct($data = null)
    {
        /* Data can be null (NIL) here. */
        if (is_null($data)) {
            $this->_data = null;
        } else {
            parent::__construct($data);
        }
    }

    /**
     */
    public function __toString()
    {
        return is_null($this->_data)
            ? ''
            : parent::__toString();
    }

    /**
     */
    public function escape()
    {
        return is_null($this->_data)
            ? 'NIL'
            : parent::escape();
    }

    /**
     */
    public function quoted()
    {
        return is_null($this->_data)
            ? false
            : parent::quoted();
    }

}
