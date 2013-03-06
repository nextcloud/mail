<?php
/**
 * Implementation of Horde_Stream for a PHP temporary stream.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Stream
 */
class Horde_Stream_Temp extends Horde_Stream
{
    /**
     * Constructor.
     *
     * @param array $opts  Additional configuration options:
     *   - max_memory: (integer) The maximum amount of memory to allocate to
     *                 the PHP temporary stream.
     *
     * @throws Horde_Stream_Exception
     */
    public function __construct(array $opts = array())
    {
        $opts = array_merge(array(
            // 2 MB is the PHP default
            'max_memory' => 2097152
        ), $opts);

        if (($this->stream = @fopen('php://temp/maxmemory:' . $opts['max_memory'], 'r+')) === false) {
            throw new Horde_Stream_Exception('Failed to open temporary memory stream.');
        }

        parent::__construct($opts);
    }

}
