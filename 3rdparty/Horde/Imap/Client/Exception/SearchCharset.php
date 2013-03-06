<?php
/**
 * Exception thrown if search query text cannot be converted to different
 * charset.
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
class Horde_Imap_Client_Exception_SearchCharset extends Horde_Imap_Client_Exception
{
    /**
     * Charset that was attempted to be converted to.
     *
     * @var string
     */
    public $charset;

    /**
     * Constructor.
     *
     * @param string $charset  The charset that was attempted to be converted
     *                         to.
     */
    public function __construct($charset)
    {
        $this->charset = $charset;

        parent::__construct(
            Horde_Imap_Client_Translation::t("Cannot convert search query text to new charset"),
            self::BADCHARSET
        );
    }

}
